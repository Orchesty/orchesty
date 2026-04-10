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
	originalDomainSuffix := config.Kong.DomainSuffix
	originalEnabled := config.Kong.Enabled

	config.Kong.AdminURL = serverURL
	config.Kong.DomainSuffix = "eu1.cloud.orchesty.io"
	config.Kong.Enabled = true

	t.Cleanup(func() {
		config.Kong.AdminURL = originalAdminURL
		config.Kong.DomainSuffix = originalDomainSuffix
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

	// 3 services (PUT) + 3 routes (POST) = 6 requests
	if len(requests) != 6 {
		t.Fatalf("expected 6 requests, got %d", len(requests))
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
	hosts := routeBody["hosts"].([]any)
	if hosts[0] != "ui-myapp-abc123.eu1.cloud.orchesty.io" {
		t.Fatalf("expected host ui-myapp-abc123.eu1.cloud.orchesty.io, got %v", hosts[0])
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
	}

	for i, expectedHost := range expectedHosts {
		routeIdx := i*2 + 1 // routes are at indices 1, 3, 5
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

	if len(requests) != 6 {
		t.Fatalf("expected 6 requests, got %d", len(requests))
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
	}

	for i, expected := range expectedRequests {
		if requests[i].Method != expected.method || requests[i].Path != expected.path {
			t.Fatalf("request %d: expected %s %s, got %s %s", i, expected.method, expected.path, requests[i].Method, requests[i].Path)
		}
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

	if len(requests) != 6 {
		t.Fatalf("expected 6 requests, got %d", len(requests))
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
