package ingressGW

import (
	"bytes"
	"encoding/json"
	"errors"
	"fmt"
	"io"
	"net/http"
	"time"

	"cloud-controller/pkg/config"
	"cloud-controller/pkg/models"
)

var errNotFound = errors.New("not found")

type serviceEntry struct {
	name            string
	url             string
	host            string
	routeConfig     routeConfig
	rateLimitConfig *rateLimitConfig
}

type routeConfig struct {
	protocols []string
	methods   []string
}

type rateLimitConfig struct {
	second  int
	minute  int
	hour    int
	day     int
	month   int
	year    int
	policy  string
	limitBy string
	redis   *redisRateLimitConfig
}

type redisRateLimitConfig struct {
	host       string
	port       int
	username   string
	password   string
	database   int
	ssl        bool
	sslVerify  bool
	timeout    int
	serverName string
}

var serviceSuffixes = []string{"fe", "be", "sp", "tp", "wa", "ws", "ses"}
var optionalServiceSuffixes = []string{"grafana", "applinth-marketplace-ui"}

type Client struct {
	httpClient *http.Client
}

func NewClient() *Client {
	return &Client{
		httpClient: &http.Client{Timeout: 15 * time.Second},
	}
}

func (c *Client) RegisterServices(dto *models.InstanceDTO) error {
	entries, err := buildServiceEntries(dto)
	if err != nil {
		return err
	}

	for _, entry := range entries {
		if err := c.upsertService(entry); err != nil {
			return fmt.Errorf("register kong service %s: %w", entry.name, err)
		}

		if err := c.createRoute(entry); err != nil {
			return fmt.Errorf("register kong route for %s: %w", entry.name, err)
		}

		if dto.Customizations.RateLimits.Enabled {
			if err := c.createRateLimitPlugin(entry); err != nil {
				return fmt.Errorf("register kong rate-limit plugin for %s: %w", entry.name, err)
			}
		}
	}

	return nil
}

func (c *Client) UpdateServices(dto *models.InstanceDTO) error {
	entries, err := buildServiceEntries(dto)
	if err != nil {
		return err
	}

	for _, entry := range entries {
		if err := c.upsertService(entry); err != nil {
			return fmt.Errorf("update kong service %s: %w", entry.name, err)
		}

		if err := c.updateRoute(entry); err != nil {
			if err := c.createRoute(entry); err != nil {
				return fmt.Errorf("register kong route for %s: %w", entry.name, err)
			}
		}

		if err := c.upsertRateLimitPlugin(entry); err != nil {
			return fmt.Errorf("update kong rate-limit plugin for %s: %w", entry.name, err)
		}
	}

	return nil
}

func (c *Client) DeleteServices(instance string) error {
	for _, suffix := range serviceSuffixes {
		c.deleteRoute(instance, suffix)
	}

	for _, suffix := range optionalServiceSuffixes {
		c.deleteRoute(instance, suffix)
	}

	return nil
}

func (c *Client) Health() error {
	return c.sendRequest(http.MethodGet, "/status", nil)
}

func (c *Client) upsertService(entry serviceEntry) error {
	payload := map[string]string{
		"name": entry.name,
		"url":  entry.url,
	}

	return c.sendRequest(http.MethodPut, fmt.Sprintf("/services/%s", entry.name), payload)
}

func (c *Client) createRoute(entry serviceEntry) error {
	payload := map[string]any{
		"name":      entry.name + "-route",
		"hosts":     []string{entry.host},
		"protocols": entry.routeConfig.protocols,
	}

	if len(entry.routeConfig.methods) > 0 {
		payload["methods"] = entry.routeConfig.methods
	}

	return c.sendRequest(http.MethodPost, fmt.Sprintf("/services/%s/routes", entry.name), payload)
}

func (c *Client) updateRoute(entry serviceEntry) error {
	payload := map[string]any{
		"hosts":     []string{entry.host},
		"protocols": entry.routeConfig.protocols,
	}

	if len(entry.routeConfig.methods) > 0 {
		payload["methods"] = entry.routeConfig.methods
	}

	return c.sendRequest(http.MethodPatch, fmt.Sprintf("/routes/%s", entry.name+"-route"), payload)
}

func (c *Client) createRateLimitPlugin(entry serviceEntry) error {
	if entry.rateLimitConfig == nil {
		return nil
	}

	payload := map[string]any{
		"name":   "rate-limiting",
		"config": entry.rateLimitConfig.toPayload(),
	}

	return c.sendRequest(http.MethodPost, fmt.Sprintf("/routes/%s/plugins", entry.name+"-route"), payload)
}

func (c *Client) upsertRateLimitPlugin(entry serviceEntry) error {
	pluginID, err := c.getRouteRateLimitPluginID(entry.name + "-route")
	if err != nil {
		return err
	}

	if entry.rateLimitConfig == nil {
		if pluginID == "" {
			return nil
		}

		return c.sendRequest(http.MethodDelete, fmt.Sprintf("/plugins/%s", pluginID), nil)
	}

	if pluginID == "" {
		return c.createRateLimitPlugin(entry)
	}

	payload := map[string]any{
		"config": entry.rateLimitConfig.toPayload(),
	}

	return c.sendRequest(http.MethodPatch, fmt.Sprintf("/plugins/%s", pluginID), payload)
}

func (c *Client) getRouteRateLimitPluginID(routeName string) (string, error) {
	response := struct {
		Data []struct {
			ID string `json:"id"`
		} `json:"data"`
	}{}

	err := c.sendRequestWithResponse(http.MethodGet, fmt.Sprintf("/routes/%s/plugins?name=rate-limiting", routeName), nil, &response)
	if err != nil {
		if errors.Is(err, errNotFound) {
			return "", nil
		}

		return "", err
	}

	if len(response.Data) == 0 {
		return "", nil
	}

	return response.Data[0].ID, nil
}

func (c *Client) deleteRoute(instance, suffix string) error {
	serviceName := instance + "-" + suffix
	routeName := serviceName + "-route"

	if err := c.sendRequest(http.MethodDelete, fmt.Sprintf("/routes/%s", routeName), nil); err != nil && !errors.Is(err, errNotFound) {
		return fmt.Errorf("delete kong route %s: %w", routeName, err)
	}

	if err := c.sendRequest(http.MethodDelete, fmt.Sprintf("/services/%s", serviceName), nil); err != nil && !errors.Is(err, errNotFound) {
		return fmt.Errorf("delete kong service %s: %w", serviceName, err)
	}

	return nil
}

func (c *Client) sendRequest(method, path string, data any) error {
	return c.sendRequestWithResponse(method, path, data, nil)
}

func (c *Client) sendRequestWithResponse(method, path string, data any, response any) error {
	var body io.Reader
	if data != nil {
		payload, err := json.Marshal(data)
		if err != nil {
			return err
		}

		body = bytes.NewBuffer(payload)
	}

	requestURL := config.Kong.AdminURL + path
	req, err := http.NewRequest(method, requestURL, body)
	if err != nil {
		return err
	}

	req.Header.Set("Content-Type", "application/json")

	resp, err := c.httpClient.Do(req)
	if err != nil {
		config.Logger.Error(err)
		return err
	}
	defer resp.Body.Close()

	if resp.StatusCode == http.StatusNotFound {
		return errNotFound
	}

	if resp.StatusCode >= 400 {
		respBody, _ := io.ReadAll(resp.Body)
		return fmt.Errorf("kong API %s %s returned %d: %s", method, path, resp.StatusCode, string(respBody))
	}

	if response != nil {
		respBody, err := io.ReadAll(resp.Body)
		if err != nil {
			return err
		}

		if len(respBody) == 0 {
			return nil
		}

		if err := json.Unmarshal(respBody, response); err != nil {
			return err
		}
	}

	return nil
}

func buildServiceEntries(dto *models.InstanceDTO) ([]serviceEntry, error) {
	suffix := config.Cloud.DomainSuffix
	tpProtocol := serviceProtocolForService(dto.Instance + "-tp")
	rateLimitCfg, err := buildRateLimitConfig(dto.Customizations.RateLimits)
	if err != nil {
		return nil, err
	}

	entries := []serviceEntry{
		{
			name: dto.Instance + "-fe",
			url:  fmt.Sprintf("%s://frontend.%s.svc.cluster.local:80", serviceProtocolForService(dto.Instance+"-fe"), dto.Instance),
			host: fmt.Sprintf("ui-%s-%s.%s", dto.InstanceUrlPrefix, dto.InstanceId, suffix),
		},
		{
			name: dto.Instance + "-be",
			url:  fmt.Sprintf("%s://backend.%s.svc.cluster.local:80", serviceProtocolForService(dto.Instance+"-be"), dto.Instance),
			host: fmt.Sprintf("api-%s-%s.%s", dto.InstanceUrlPrefix, dto.InstanceId, suffix),
		},
		{
			name: dto.Instance + "-sp",
			url:  fmt.Sprintf("%s://starting-point.%s.svc.cluster.local:8080", serviceProtocolForService(dto.Instance+"-sp"), dto.Instance),
			host: fmt.Sprintf("start-%s-%s.%s", dto.InstanceUrlPrefix, dto.InstanceId, suffix),
		},
		{
			name: dto.Instance + "-tp",
			url:  fmt.Sprintf("%s://tunnel-proxy.%s.svc.cluster.local:50051", tpProtocol, dto.Instance),
			host: fmt.Sprintf("proxy-%s-%s.%s", dto.InstanceUrlPrefix, dto.InstanceId, suffix),
		},
		{
			name: dto.Instance + "-wa",
			url:  fmt.Sprintf("%s://worker-api.%s.svc.cluster.local:80", serviceProtocolForService(dto.Instance+"-wa"), dto.Instance),
			host: fmt.Sprintf("worker-%s-%s.%s", dto.InstanceUrlPrefix, dto.InstanceId, suffix),
		},
		{
			name: dto.Instance + "-ws",
			url:  fmt.Sprintf("%s://trace.%s.svc.cluster.local:8080", serviceProtocolForService(dto.Instance+"-ws"), dto.Instance),
			host: fmt.Sprintf("ws-%s-%s.%s", dto.InstanceUrlPrefix, dto.InstanceId, suffix),
		},
		{
			name: dto.Instance + "-ses",
			url:  fmt.Sprintf("%s://notifier.%s.svc.cluster.local:8080", serviceProtocolForService(dto.Instance+"-ses"), dto.Instance),
			host: fmt.Sprintf("ses-%s-%s.%s", dto.InstanceUrlPrefix, dto.InstanceId, suffix),
		},
	}

	if dto.Customizations.Logs.GrafanaEnabled {
		entries = append(entries, serviceEntry{
			name: dto.Instance + "-grafana",
			url:  fmt.Sprintf("%s://grafana.%s.svc.cluster.local:80", serviceProtocolForService(dto.Instance+"-grafana"), dto.Instance),
			host: fmt.Sprintf("grafana-%s-%s.%s", dto.InstanceUrlPrefix, dto.InstanceId, suffix),
		})
	}

	if dto.Customizations.Applinth.Enabled {
		entries = append(entries, serviceEntry{
			name: dto.Instance + "-applinth-marketplace-ui",
			url:  fmt.Sprintf("%s://applinth-marketplace-ui.%s.svc.cluster.local:80", serviceProtocolForService(dto.Instance+"-applinth-marketplace-ui"), dto.Instance),
			host: fmt.Sprintf("applinth-%s-%s.%s", dto.InstanceUrlPrefix, dto.InstanceId, suffix),
		})
	}

	for i := range entries {
		entries[i].routeConfig = routeConfigForService(entries[i].name)
		if entries[i].name == dto.Instance+"-sp" || entries[i].name == dto.Instance+"-wa" {
			entries[i].rateLimitConfig = rateLimitCfg
		}
	}

	return entries, nil
}

func routeConfigForService(serviceName string) routeConfig {
	defaultMethods := []string{
		http.MethodGet,
		http.MethodPost,
		http.MethodPut,
		http.MethodPatch,
		http.MethodDelete,
		http.MethodOptions,
	}

	if len(serviceName) >= 3 && serviceName[len(serviceName)-3:] == "-tp" {
		return routeConfig{protocols: []string{"grpc", "grpcs"}, methods: nil}
	}

	return routeConfig{protocols: []string{"https"}, methods: defaultMethods}
}

func serviceProtocolForService(serviceName string) string {
	if len(serviceName) >= 3 && serviceName[len(serviceName)-3:] == "-tp" {
		return "grpc"
	}

	return "http"
}

func buildRateLimitConfig(rateLimits models.RateLimits) (*rateLimitConfig, error) {
	if !rateLimits.Enabled {
		return nil, nil
	}

	if rateLimits.Second == 0 && rateLimits.Minute == 0 && rateLimits.Hour == 0 && rateLimits.Day == 0 && rateLimits.Month == 0 && rateLimits.Year == 0 {
		return nil, nil
	}

	policy := config.Kong.RateLimitConfig.Policy
	redisConfig := (*redisRateLimitConfig)(nil)
	if policy == "redis" {
		var err error
		redisConfig, err = buildRedisRateLimitConfig()
		if err != nil {
			return nil, err
		}
	}

	return &rateLimitConfig{
		second:  rateLimits.Second,
		minute:  rateLimits.Minute,
		hour:    rateLimits.Hour,
		day:     rateLimits.Day,
		month:   rateLimits.Month,
		year:    rateLimits.Year,
		policy:  policy,
		limitBy: config.Kong.RateLimitConfig.LimitBy,
		redis:   redisConfig,
	}, nil
}

func buildRedisRateLimitConfig() (*redisRateLimitConfig, error) {
	redisConfig := config.Kong.RateLimitConfig.Redis
	if redisConfig.Host == "" {
		return nil, errors.New("KONG_RATE_LIMIT_REDIS_HOST is required when KONG_RATE_LIMIT_POLICY=redis")
	}

	return &redisRateLimitConfig{
		host:       redisConfig.Host,
		port:       redisConfig.Port,
		username:   redisConfig.Username,
		password:   redisConfig.Password,
		database:   redisConfig.Database,
		ssl:        redisConfig.SSL,
		sslVerify:  redisConfig.SSLVerify,
		timeout:    redisConfig.Timeout,
		serverName: redisConfig.ServerName,
	}, nil
}

func (r *rateLimitConfig) toPayload() map[string]any {
	payload := map[string]any{}

	if r.second > 0 {
		payload["second"] = r.second
	}
	if r.minute > 0 {
		payload["minute"] = r.minute
	}
	if r.hour > 0 {
		payload["hour"] = r.hour
	}
	if r.day > 0 {
		payload["day"] = r.day
	}
	if r.month > 0 {
		payload["month"] = r.month
	}
	if r.year > 0 {
		payload["year"] = r.year
	}

	payload["policy"] = r.policy
	payload["limit_by"] = r.limitBy

	if r.redis != nil {
		payload["redis"] = r.redis.toPayload()
	}

	return payload
}

func (r *redisRateLimitConfig) toPayload() map[string]any {
	payload := map[string]any{
		"host":       r.host,
		"port":       r.port,
		"database":   r.database,
		"ssl":        r.ssl,
		"ssl_verify": r.sslVerify,
		"timeout":    r.timeout,
	}

	if r.username != "" {
		payload["username"] = r.username
	}
	if r.password != "" {
		payload["password"] = r.password
	}
	if r.serverName != "" {
		payload["server_name"] = r.serverName
	}

	return payload
}
