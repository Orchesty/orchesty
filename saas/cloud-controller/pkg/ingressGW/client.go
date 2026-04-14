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
	name string
	url  string
	host string
}

var serviceSuffixes = []string{"fe", "be", "sp", "tp", "ws", "ses"}
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
	entries := buildServiceEntries(dto)

	for _, entry := range entries {
		if err := c.upsertService(entry); err != nil {
			return fmt.Errorf("register kong service %s: %w", entry.name, err)
		}

		if err := c.createRoute(entry); err != nil {
			return fmt.Errorf("register kong route for %s: %w", entry.name, err)
		}
	}

	return nil
}

func (c *Client) UpdateServices(dto *models.InstanceDTO) error {
	entries := buildServiceEntries(dto)

	for _, entry := range entries {
		if err := c.upsertService(entry); err != nil {
			return fmt.Errorf("update kong service %s: %w", entry.name, err)
		}

		if err := c.updateRoute(entry); err != nil {
			return fmt.Errorf("update kong route for %s: %w", entry.name, err)
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
		"protocols": []string{"https"},
	}

	return c.sendRequest(http.MethodPost, fmt.Sprintf("/services/%s/routes", entry.name), payload)
}

func (c *Client) updateRoute(entry serviceEntry) error {
	payload := map[string]any{
		"hosts":     []string{entry.host},
		"protocols": []string{"https"},
	}

	return c.sendRequest(http.MethodPatch, fmt.Sprintf("/routes/%s", entry.name+"-route"), payload)
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

	return nil
}

func buildServiceEntries(dto *models.InstanceDTO) []serviceEntry {
	suffix := config.Cloud.DomainSuffix

	entries := []serviceEntry{
		{
			name: dto.Instance + "-fe",
			url:  fmt.Sprintf("http://frontend.%s.svc.cluster.local:80", dto.Instance),
			host: fmt.Sprintf("ui-%s-%s.%s", dto.InstanceUrlPrefix, dto.InstanceId, suffix),
		},
		{
			name: dto.Instance + "-be",
			url:  fmt.Sprintf("http://backend.%s.svc.cluster.local:80", dto.Instance),
			host: fmt.Sprintf("api-%s-%s.%s", dto.InstanceUrlPrefix, dto.InstanceId, suffix),
		},
		{
			name: dto.Instance + "-sp",
			url:  fmt.Sprintf("http://starting-point.%s.svc.cluster.local:8080", dto.Instance),
			host: fmt.Sprintf("start-%s-%s.%s", dto.InstanceUrlPrefix, dto.InstanceId, suffix),
		},
		{
			name: dto.Instance + "-tp",
			url:  fmt.Sprintf("http://tunnel-proxy.%s.svc.cluster.local:8080", dto.Instance),
			host: fmt.Sprintf("proxy-%s-%s.%s", dto.InstanceUrlPrefix, dto.InstanceId, suffix),
		},
		{
			name: dto.Instance + "-ws",
			url:  fmt.Sprintf("http://trace.%s.svc.cluster.local:8080", dto.Instance),
			host: fmt.Sprintf("ws-%s-%s.%s", dto.InstanceUrlPrefix, dto.InstanceId, suffix),
		},
		{
			name: dto.Instance + "-ses",
			url:  fmt.Sprintf("http://notifier.%s.svc.cluster.local:8080", dto.Instance),
			host: fmt.Sprintf("ses-%s-%s.%s", dto.InstanceUrlPrefix, dto.InstanceId, suffix),
		},
	}

	if dto.Customizations.Logs.GrafanaEnabled {
		entries = append(entries, serviceEntry{
			name: dto.Instance + "-grafana",
			url:  fmt.Sprintf("http://grafana.%s.svc.cluster.local:80", dto.Instance),
			host: fmt.Sprintf("grafana-%s-%s.%s", dto.InstanceUrlPrefix, dto.InstanceId, suffix),
		})
	}

	if dto.Customizations.Applinth.Enabled {
		entries = append(entries, serviceEntry{
			name: dto.Instance + "-applinth-marketplace-ui",
			url:  fmt.Sprintf("http://applinth-marketplace-ui.%s.svc.cluster.local:80", dto.Instance),
			host: fmt.Sprintf("applinth-%s-%s.%s", dto.InstanceUrlPrefix, dto.InstanceId, suffix),
		})
	}

	return entries
}
