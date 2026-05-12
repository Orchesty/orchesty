package ingressGW

import (
	"encoding/json"
	"io"
	"net/http"
	"net/http/httptest"
	"testing"

	"cloud-controller/pkg/config"
	"cloud-controller/pkg/models"
)

type capturedRequest struct {
	Method string
	Path   string
	Body   map[string]any
}

func withKongConfig(t *testing.T, serverURL string) {
	t.Helper()

	originalAdminURL := config.Kong.AdminURL
	originalDomainSuffix := config.Cloud.DomainSuffix
	originalEnabled := config.Kong.Enabled
	originalRateLimitPolicy := config.Kong.RateLimitConfig.Policy
	originalRateLimitLimitBy := config.Kong.RateLimitConfig.LimitBy
	originalRateLimitRedis := config.Kong.RateLimitConfig.Redis

	config.Kong.AdminURL = serverURL
	config.Cloud.DomainSuffix = "eu1.cloud.orchesty.io"
	config.Kong.Enabled = true
	config.Kong.RateLimitConfig.Policy = "local"
	config.Kong.RateLimitConfig.LimitBy = "ip"
	config.Kong.RateLimitConfig.Redis = struct {
		Host       string `env:"KONG_RATE_LIMIT_REDIS_HOST" default:""`
		Port       int    `env:"KONG_RATE_LIMIT_REDIS_PORT" default:"6379"`
		Username   string `env:"KONG_RATE_LIMIT_REDIS_USERNAME" default:""`
		Password   string `env:"KONG_RATE_LIMIT_REDIS_PASSWORD" default:""`
		Database   int    `env:"KONG_RATE_LIMIT_REDIS_DATABASE" default:"0"`
		SSL        bool   `env:"KONG_RATE_LIMIT_REDIS_SSL" default:"false"`
		SSLVerify  bool   `env:"KONG_RATE_LIMIT_REDIS_SSL_VERIFY" default:"true"`
		Timeout    int    `env:"KONG_RATE_LIMIT_REDIS_TIMEOUT" default:"2000"`
		ServerName string `env:"KONG_RATE_LIMIT_REDIS_SERVER_NAME" default:""`
	}{}

	t.Cleanup(func() {
		config.Kong.AdminURL = originalAdminURL
		config.Cloud.DomainSuffix = originalDomainSuffix
		config.Kong.Enabled = originalEnabled
		config.Kong.RateLimitConfig.Policy = originalRateLimitPolicy
		config.Kong.RateLimitConfig.LimitBy = originalRateLimitLimitBy
		config.Kong.RateLimitConfig.Redis = originalRateLimitRedis
	})
}

func testDTO() *models.InstanceDTO {
	return &models.InstanceDTO{
		Instance:          "instance-abc123",
		InstanceId:        "abc123",
		InstanceUrlPrefix: "myapp",
	}
}

func testDTOWithRateLimits() *models.InstanceDTO {
	dto := testDTO()
	dto.Customizations = models.Customizations{
		RateLimits: models.RateLimits{
			Enabled: true,
			Minute:  120,
			Hour:    1000,
		},
	}

	return dto
}

func decodeBody(t *testing.T, request *http.Request) map[string]any {
	t.Helper()

	bodyBytes, err := io.ReadAll(request.Body)
	if err != nil {
		t.Fatalf("failed to read request body: %v", err)
	}

	if len(bodyBytes) == 0 {
		return nil
	}

	body := map[string]any{}
	if err := json.Unmarshal(bodyBytes, &body); err != nil {
		t.Fatalf("failed to decode request body: %v", err)
	}

	return body
}

func TestRegisterServicesSuccess(t *testing.T) {
	requests := make([]capturedRequest, 0)
	server := httptest.NewServer(http.HandlerFunc(func(writer http.ResponseWriter, request *http.Request) {
		requests = append(requests, capturedRequest{
			Method: request.Method,
			Path:   request.URL.Path,
			Body:   decodeBody(t, request),
		})
		writer.WriteHeader(http.StatusCreated)
	}))
	defer server.Close()

	withKongConfig(t, server.URL)

	client := NewClient()
	if err := client.RegisterServices(testDTO()); err != nil {
		t.Fatalf("expected no error, got %v", err)
	}

	if len(requests) != 14 {
		t.Fatalf("expected 14 requests, got %d", len(requests))
	}

	expectedRequests := []struct {
		method string
		path   string
	}{
		{http.MethodPut, "/services/instance-abc123-fe"},
		{http.MethodPost, "/services/instance-abc123-fe/routes"},
		{http.MethodPut, "/services/instance-abc123-be"},
		{http.MethodPost, "/services/instance-abc123-be/routes"},
		{http.MethodPut, "/services/instance-abc123-sp"},
		{http.MethodPost, "/services/instance-abc123-sp/routes"},
		{http.MethodPut, "/services/instance-abc123-tp"},
		{http.MethodPost, "/services/instance-abc123-tp/routes"},
		{http.MethodPut, "/services/instance-abc123-wa"},
		{http.MethodPost, "/services/instance-abc123-wa/routes"},
		{http.MethodPut, "/services/instance-abc123-ws"},
		{http.MethodPost, "/services/instance-abc123-ws/routes"},
		{http.MethodPut, "/services/instance-abc123-ses"},
		{http.MethodPost, "/services/instance-abc123-ses/routes"},
	}

	for i, expected := range expectedRequests {
		if requests[i].Method != expected.method || requests[i].Path != expected.path {
			t.Fatalf("request %d: expected %s %s, got %s %s", i, expected.method, expected.path, requests[i].Method, requests[i].Path)
		}
	}
	if requests[0].Body["name"] != "instance-abc123-fe" {
		t.Fatalf("expected service name instance-abc123-fe, got %v", requests[0].Body["name"])
	}

	// Verify first route payload
	routeBody := requests[1].Body
	if routeBody["name"] != "instance-abc123-fe-route" {
		t.Fatalf("expected route name instance-abc123-fe-route, got %v", routeBody["name"])
	}
	protocols := routeBody["protocols"].([]any)
	if protocols[0] != "https" {
		t.Fatalf("expected protocol https for fe route, got %v", protocols)
	}
	methods := routeBody["methods"].([]any)
	containsOptions := false
	for _, method := range methods {
		if method == http.MethodOptions {
			containsOptions = true
			break
		}
	}
	if !containsOptions {
		t.Fatalf("expected OPTIONS in route methods, got %v", methods)
	}
	hosts := routeBody["hosts"].([]any)
	if hosts[0] != "ui-myapp-abc123.eu1.cloud.orchesty.io" {
		t.Fatalf("expected host ui-myapp-abc123.eu1.cloud.orchesty.io, got %v", hosts[0])
	}

	tpRouteBody := requests[7].Body
	tpProtocols := tpRouteBody["protocols"].([]any)
	if len(tpProtocols) != 2 || tpProtocols[0] != "grpc" || tpProtocols[1] != "grpcs" {
		t.Fatalf("expected tunnel-proxy route protocols [grpc grpcs], got %v", tpProtocols)
	}
	if _, hasMethods := tpRouteBody["methods"]; hasMethods {
		t.Fatalf("expected tunnel-proxy route payload without methods, got %v", tpRouteBody["methods"])
	}

	tpServiceBody := requests[6].Body
	if tpServiceBody["url"] != "grpc://tunnel-proxy.instance-abc123.svc.cluster.local:50051" {
		t.Fatalf("expected tunnel-proxy service URL grpc://tunnel-proxy.instance-abc123.svc.cluster.local:50051, got %v", tpServiceBody["url"])
	}

	wsRouteBody := requests[9].Body
	wsProtocols := wsRouteBody["protocols"].([]any)
	if len(wsProtocols) != 1 || wsProtocols[0] != "https" {
		t.Fatalf("expected ws route protocols [https], got %v", wsProtocols)
	}
}

func TestRegisterServicesHostFormats(t *testing.T) {
	requests := make([]capturedRequest, 0)
	server := httptest.NewServer(http.HandlerFunc(func(writer http.ResponseWriter, request *http.Request) {
		requests = append(requests, capturedRequest{
			Method: request.Method,
			Path:   request.URL.Path,
			Body:   decodeBody(t, request),
		})
		writer.WriteHeader(http.StatusCreated)
	}))
	defer server.Close()

	withKongConfig(t, server.URL)

	client := NewClient()
	if err := client.RegisterServices(testDTO()); err != nil {
		t.Fatalf("expected no error, got %v", err)
	}

	expectedHosts := []string{
		"ui-myapp-abc123.eu1.cloud.orchesty.io",
		"api-myapp-abc123.eu1.cloud.orchesty.io",
		"start-myapp-abc123.eu1.cloud.orchesty.io",
		"proxy-myapp-abc123.eu1.cloud.orchesty.io",
		"worker-myapp-abc123.eu1.cloud.orchesty.io",
		"ws-myapp-abc123.eu1.cloud.orchesty.io",
		"ses-myapp-abc123.eu1.cloud.orchesty.io",
	}

	for i, expectedHost := range expectedHosts {
		routeIdx := i*2 + 1 // routes are at indices 1, 3, 5, 7, 9
		hosts := requests[routeIdx].Body["hosts"].([]any)
		if hosts[0] != expectedHost {
			t.Fatalf("route %d: expected host %s, got %v", i, expectedHost, hosts[0])
		}
	}
}

func TestUpdateServicesSuccess(t *testing.T) {
	requests := make([]capturedRequest, 0)
	server := httptest.NewServer(http.HandlerFunc(func(writer http.ResponseWriter, request *http.Request) {
		requests = append(requests, capturedRequest{
			Method: request.Method,
			Path:   request.URL.Path,
			Body:   decodeBody(t, request),
		})
		writer.WriteHeader(http.StatusOK)
	}))
	defer server.Close()

	withKongConfig(t, server.URL)

	client := NewClient()
	if err := client.UpdateServices(testDTO()); err != nil {
		t.Fatalf("expected no error, got %v", err)
	}

	if len(requests) != 15 {
		t.Fatalf("expected 15 requests, got %d", len(requests))
	}

	expectedRequests := []struct {
		method string
		path   string
	}{
		{http.MethodPut, "/services/instance-abc123-fe"},
		{http.MethodPatch, "/routes/instance-abc123-fe-route"},
		{http.MethodPut, "/services/instance-abc123-be"},
		{http.MethodPatch, "/routes/instance-abc123-be-route"},
		{http.MethodPut, "/services/instance-abc123-sp"},
		{http.MethodPatch, "/routes/instance-abc123-sp-route"},
		{http.MethodGet, "/routes/instance-abc123-sp-route/plugins"},
		{http.MethodPut, "/services/instance-abc123-tp"},
		{http.MethodPatch, "/routes/instance-abc123-tp-route"},
		{http.MethodPut, "/services/instance-abc123-wa"},
		{http.MethodPatch, "/routes/instance-abc123-wa-route"},
		{http.MethodPut, "/services/instance-abc123-ws"},
		{http.MethodPatch, "/routes/instance-abc123-ws-route"},
		{http.MethodPut, "/services/instance-abc123-ses"},
		{http.MethodPatch, "/routes/instance-abc123-ses-route"},
	}

	for i, expected := range expectedRequests {
		if requests[i].Method != expected.method || requests[i].Path != expected.path {
			t.Fatalf("request %d: expected %s %s, got %s %s", i, expected.method, expected.path, requests[i].Method, requests[i].Path)
		}
	}
	wsUpdateBody := requests[12].Body
	wsProtocols := wsUpdateBody["protocols"].([]any)
	if len(wsProtocols) != 1 || wsProtocols[0] != "https" {
		t.Fatalf("expected ws route protocols [https] on update, got %v", wsProtocols)
	}
	tpUpdateBody := requests[8].Body
	if _, hasMethods := tpUpdateBody["methods"]; hasMethods {
		t.Fatalf("expected tunnel-proxy update payload without methods, got %v", tpUpdateBody["methods"])
	}
	methods := wsUpdateBody["methods"].([]any)
	containsOptions := false
	for _, method := range methods {
		if method == http.MethodOptions {
			containsOptions = true
			break
		}
	}
	if !containsOptions {
		t.Fatalf("expected OPTIONS in update route methods, got %v", methods)
	}
}

func TestUpdateServicesDeletesRateLimitPluginsWhenDisabled(t *testing.T) {
	requests := make([]capturedRequest, 0)
	server := httptest.NewServer(http.HandlerFunc(func(writer http.ResponseWriter, request *http.Request) {
		requests = append(requests, capturedRequest{
			Method: request.Method,
			Path:   request.URL.Path,
			Body:   decodeBody(t, request),
		})

		if request.Method == http.MethodGet && request.URL.Query().Get("name") == "rate-limiting" {
			writer.WriteHeader(http.StatusOK)
			_, _ = writer.Write([]byte(`{"data":[{"id":"plugin-to-delete"}]}`))
			return
		}

		writer.WriteHeader(http.StatusOK)
	}))
	defer server.Close()

	withKongConfig(t, server.URL)

	client := NewClient()
	if err := client.UpdateServices(testDTO()); err != nil {
		t.Fatalf("expected no error, got %v", err)
	}

	// Expected requests:
	// - 7 services * (PUT service + PATCH route) = 14
	// - 1 service with rate limiting (sp) * (GET plugins + DELETE) = 2
	// Total: 16 requests
	if len(requests) != 16 {
		t.Fatalf("expected 16 requests, got %d", len(requests))
	}

	// Check that rate limit plugin is deleted only for sp service (requests 6-7)
	if requests[6].Method != http.MethodGet || requests[6].Path != "/routes/instance-abc123-sp-route/plugins" {
		t.Fatalf("expected GET plugin lookup for sp route, got %s %s", requests[6].Method, requests[6].Path)
	}

	if requests[7].Method != http.MethodDelete || requests[7].Path != "/plugins/plugin-to-delete" {
		t.Fatalf("expected DELETE /plugins/plugin-to-delete for sp route, got %s %s", requests[7].Method, requests[7].Path)
	}
}

func TestRegisterServicesCreatesRateLimitPlugins(t *testing.T) {
	requests := make([]capturedRequest, 0)
	server := httptest.NewServer(http.HandlerFunc(func(writer http.ResponseWriter, request *http.Request) {
		requests = append(requests, capturedRequest{
			Method: request.Method,
			Path:   request.URL.Path,
			Body:   decodeBody(t, request),
		})
		writer.WriteHeader(http.StatusCreated)
	}))
	defer server.Close()

	withKongConfig(t, server.URL)

	client := NewClient()
	if err := client.RegisterServices(testDTOWithRateLimits()); err != nil {
		t.Fatalf("expected no error, got %v", err)
	}

	if len(requests) != 15 {
		t.Fatalf("expected 15 requests, got %d", len(requests))
	}

	pluginRequests := make([]capturedRequest, 0)
	for _, req := range requests {
		if req.Method == http.MethodPost && req.Path == "/routes/instance-abc123-sp-route/plugins" {
			pluginRequests = append(pluginRequests, req)
		}
	}

	if len(pluginRequests) != 1 {
		t.Fatalf("expected 1 plugin create request, got %d", len(pluginRequests))
	}

	pluginBody := pluginRequests[0].Body
	if pluginBody["name"] != "rate-limiting" {
		t.Fatalf("expected plugin name rate-limiting, got %v", pluginBody["name"])
	}

	configBody := pluginBody["config"].(map[string]any)
	if configBody["minute"] != float64(120) {
		t.Fatalf("expected minute=120, got %v", configBody["minute"])
	}
	if configBody["hour"] != float64(1000) {
		t.Fatalf("expected hour=1000, got %v", configBody["hour"])
	}
	if configBody["policy"] != "local" {
		t.Fatalf("expected policy=local, got %v", configBody["policy"])
	}
	if configBody["limit_by"] != "ip" {
		t.Fatalf("expected limit_by=ip, got %v", configBody["limit_by"])
	}
}

func TestUpdateServicesUpsertsRateLimitPlugins(t *testing.T) {
	requests := make([]capturedRequest, 0)
	server := httptest.NewServer(http.HandlerFunc(func(writer http.ResponseWriter, request *http.Request) {
		requests = append(requests, capturedRequest{
			Method: request.Method,
			Path:   request.URL.Path,
			Body:   decodeBody(t, request),
		})

		if request.Method == http.MethodGet && request.URL.Query().Get("name") == "rate-limiting" {
			writer.WriteHeader(http.StatusOK)
			_, _ = writer.Write([]byte(`{"data":[{"id":"plugin-123"}]}`))
			return
		}

		writer.WriteHeader(http.StatusOK)
	}))
	defer server.Close()

	withKongConfig(t, server.URL)

	client := NewClient()
	if err := client.UpdateServices(testDTOWithRateLimits()); err != nil {
		t.Fatalf("expected no error, got %v", err)
	}

	if len(requests) != 16 {
		t.Fatalf("expected 16 requests, got %d", len(requests))
	}

	if requests[6].Method != http.MethodGet || requests[6].Path != "/routes/instance-abc123-sp-route/plugins" {
		t.Fatalf("expected GET plugin lookup for sp route at index 6, got %s %s", requests[6].Method, requests[6].Path)
	}

	patchPluginRequests := 0
	deletePluginRequests := 0
	var patchConfig map[string]any

	for _, req := range requests {
		if req.Path == "/plugins/plugin-123" && req.Method == http.MethodPatch {
			patchPluginRequests++
			if patchConfig == nil {
				patchConfig = req.Body["config"].(map[string]any)
			}
		}
		if req.Path == "/plugins/plugin-123" && req.Method == http.MethodDelete {
			deletePluginRequests++
		}
	}

	if patchPluginRequests != 1 {
		t.Fatalf("expected 1 PATCH plugin request for sp, got %d", patchPluginRequests)
	}

	if deletePluginRequests != 0 {
		t.Fatalf("expected 0 DELETE plugin requests (rate limits applied only to sp), got %d", deletePluginRequests)
	}

	if patchConfig["minute"] != float64(120) {
		t.Fatalf("expected minute=120, got %v", patchConfig["minute"])
	}
	if patchConfig["hour"] != float64(1000) {
		t.Fatalf("expected hour=1000, got %v", patchConfig["hour"])
	}
	if patchConfig["policy"] != "local" {
		t.Fatalf("expected policy=local, got %v", patchConfig["policy"])
	}
	if patchConfig["limit_by"] != "ip" {
		t.Fatalf("expected limit_by=ip, got %v", patchConfig["limit_by"])
	}
}

func TestRegisterServicesCreatesRedisRateLimitPlugins(t *testing.T) {
	requests := make([]capturedRequest, 0)
	server := httptest.NewServer(http.HandlerFunc(func(writer http.ResponseWriter, request *http.Request) {
		requests = append(requests, capturedRequest{
			Method: request.Method,
			Path:   request.URL.Path,
			Body:   decodeBody(t, request),
		})
		writer.WriteHeader(http.StatusCreated)
	}))
	defer server.Close()

	withKongConfig(t, server.URL)
	config.Kong.RateLimitConfig.Policy = "redis"
	config.Kong.RateLimitConfig.Redis.Host = "rate-limit-redis"
	config.Kong.RateLimitConfig.Redis.Port = 6379
	config.Kong.RateLimitConfig.Redis.Username = "default"
	config.Kong.RateLimitConfig.Redis.Password = "secret"
	config.Kong.RateLimitConfig.Redis.Database = 2
	config.Kong.RateLimitConfig.Redis.SSL = true
	config.Kong.RateLimitConfig.Redis.SSLVerify = false
	config.Kong.RateLimitConfig.Redis.Timeout = 5000
	config.Kong.RateLimitConfig.Redis.ServerName = "redis.internal"

	client := NewClient()
	if err := client.RegisterServices(testDTOWithRateLimits()); err != nil {
		t.Fatalf("expected no error, got %v", err)
	}

	pluginRequests := make([]capturedRequest, 0)
	for _, req := range requests {
		if req.Method == http.MethodPost && req.Path == "/routes/instance-abc123-sp-route/plugins" {
			pluginRequests = append(pluginRequests, req)
		}
	}

	if len(pluginRequests) != 1 {
		t.Fatalf("expected 1 plugin create request, got %d", len(pluginRequests))
	}

	pluginBody := pluginRequests[0].Body
	configBody := pluginBody["config"].(map[string]any)
	if configBody["policy"] != "redis" {
		t.Fatalf("expected policy=redis, got %v", configBody["policy"])
	}

	redisBody := configBody["redis"].(map[string]any)
	if redisBody["host"] != "rate-limit-redis" {
		t.Fatalf("expected redis host rate-limit-redis, got %v", redisBody["host"])
	}
	if redisBody["port"] != float64(6379) {
		t.Fatalf("expected redis port 6379, got %v", redisBody["port"])
	}
	if redisBody["username"] != "default" {
		t.Fatalf("expected redis username default, got %v", redisBody["username"])
	}
	if redisBody["password"] != "secret" {
		t.Fatalf("expected redis password secret, got %v", redisBody["password"])
	}
	if redisBody["database"] != float64(2) {
		t.Fatalf("expected redis database 2, got %v", redisBody["database"])
	}
	if redisBody["ssl"] != true {
		t.Fatalf("expected redis ssl true, got %v", redisBody["ssl"])
	}
	if redisBody["ssl_verify"] != false {
		t.Fatalf("expected redis ssl_verify false, got %v", redisBody["ssl_verify"])
	}
	if redisBody["timeout"] != float64(5000) {
		t.Fatalf("expected redis timeout 5000, got %v", redisBody["timeout"])
	}
	if redisBody["server_name"] != "redis.internal" {
		t.Fatalf("expected redis server_name redis.internal, got %v", redisBody["server_name"])
	}
}

func TestRegisterServicesRedisRateLimitRequiresHost(t *testing.T) {
	withKongConfig(t, "http://example.test")
	config.Kong.RateLimitConfig.Policy = "redis"
	config.Kong.RateLimitConfig.Redis.Host = ""

	client := NewClient()
	err := client.RegisterServices(testDTOWithRateLimits())
	if err == nil || err.Error() != "KONG_RATE_LIMIT_REDIS_HOST is required when KONG_RATE_LIMIT_POLICY=redis" {
		t.Fatalf("expected missing redis host error, got %v", err)
	}
}

func TestDeleteServicesSuccess(t *testing.T) {
	requests := make([]capturedRequest, 0)
	server := httptest.NewServer(http.HandlerFunc(func(writer http.ResponseWriter, request *http.Request) {
		requests = append(requests, capturedRequest{
			Method: request.Method,
			Path:   request.URL.Path,
		})
		writer.WriteHeader(http.StatusNoContent)
	}))
	defer server.Close()

	withKongConfig(t, server.URL)

	client := NewClient()
	if err := client.DeleteServices("instance-abc123"); err != nil {
		t.Fatalf("expected no error, got %v", err)
	}

	if len(requests) != 18 {
		t.Fatalf("expected 18 requests, got %d", len(requests))
	}

	expectedRequests := []struct {
		method string
		path   string
	}{
		{http.MethodDelete, "/routes/instance-abc123-fe-route"},
		{http.MethodDelete, "/services/instance-abc123-fe"},
		{http.MethodDelete, "/routes/instance-abc123-be-route"},
		{http.MethodDelete, "/services/instance-abc123-be"},
		{http.MethodDelete, "/routes/instance-abc123-sp-route"},
		{http.MethodDelete, "/services/instance-abc123-sp"},
		{http.MethodDelete, "/routes/instance-abc123-tp-route"},
		{http.MethodDelete, "/services/instance-abc123-tp"},
		{http.MethodDelete, "/routes/instance-abc123-wa-route"},
		{http.MethodDelete, "/services/instance-abc123-wa"},
		{http.MethodDelete, "/routes/instance-abc123-ws-route"},
		{http.MethodDelete, "/services/instance-abc123-ws"},
		{http.MethodDelete, "/routes/instance-abc123-ses-route"},
		{http.MethodDelete, "/services/instance-abc123-ses"},
		{http.MethodDelete, "/routes/instance-abc123-grafana-route"},
		{http.MethodDelete, "/services/instance-abc123-grafana"},
		{http.MethodDelete, "/routes/instance-abc123-applinth-marketplace-ui-route"},
		{http.MethodDelete, "/services/instance-abc123-applinth-marketplace-ui"},
	}

	for i, expected := range expectedRequests {
		if requests[i].Method != expected.method || requests[i].Path != expected.path {
			t.Fatalf("request %d: expected %s %s, got %s %s", i, expected.method, expected.path, requests[i].Method, requests[i].Path)
		}
	}
}

func TestRegisterServicesAPIError(t *testing.T) {
	server := httptest.NewServer(http.HandlerFunc(func(writer http.ResponseWriter, _ *http.Request) {
		writer.WriteHeader(http.StatusInternalServerError)
		_, _ = writer.Write([]byte(`{"message":"internal error"}`))
	}))
	defer server.Close()

	withKongConfig(t, server.URL)

	client := NewClient()
	err := client.RegisterServices(testDTO())
	if err == nil {
		t.Fatal("expected error")
	}
}

func TestHealthSuccess(t *testing.T) {
	server := httptest.NewServer(http.HandlerFunc(func(writer http.ResponseWriter, _ *http.Request) {
		writer.WriteHeader(http.StatusOK)
	}))
	defer server.Close()

	withKongConfig(t, server.URL)

	client := NewClient()
	if err := client.Health(); err != nil {
		t.Fatalf("expected no error, got %v", err)
	}
}

func TestHealthError(t *testing.T) {
	server := httptest.NewServer(http.HandlerFunc(func(writer http.ResponseWriter, _ *http.Request) {
		writer.WriteHeader(http.StatusServiceUnavailable)
		_, _ = writer.Write([]byte(`{"message":"unhealthy"}`))
	}))
	defer server.Close()

	withKongConfig(t, server.URL)

	client := NewClient()
	err := client.Health()
	if err == nil {
		t.Fatal("expected error")
	}
}
