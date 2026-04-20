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

	config.Kong.AdminURL = serverURL
	config.Cloud.DomainSuffix = "eu1.cloud.orchesty.io"
	config.Kong.Enabled = true

	t.Cleanup(func() {
		config.Kong.AdminURL = originalAdminURL
		config.Cloud.DomainSuffix = originalDomainSuffix
		config.Kong.Enabled = originalEnabled
	})
}

func testDTO() *models.InstanceDTO {
	return &models.InstanceDTO{
		Instance:          "instance-abc123",
		InstanceId:        "abc123",
		InstanceUrlPrefix: "myapp",
	}
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

	// Verify first service payload
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

	if len(requests) != 14 {
		t.Fatalf("expected 14 requests, got %d", len(requests))
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

	wsUpdateBody := requests[9].Body
	wsProtocols := wsUpdateBody["protocols"].([]any)
	if len(wsProtocols) != 1 || wsProtocols[0] != "https" {
		t.Fatalf("expected ws route protocols [https] on update, got %v", wsProtocols)
	}
	tpUpdateBody := requests[7].Body
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

	if len(requests) != 16 {
		t.Fatalf("expected 16 requests, got %d", len(requests))
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
