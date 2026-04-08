package rabbitmq

import (
	"encoding/json"
	"io"
	"net/http"
	"net/http/httptest"
	"net/url"
	"testing"

	"cloud-controller/pkg/config"
	"cloud-controller/pkg/models"
)

type capturedRequest struct {
	Method string
	Path   string
	User   string
	Pass   string
	Body   map[string]string
}

func withRabbitConfig(t *testing.T, serverURL, user, pass string) {
	t.Helper()

	parsed, err := url.Parse(serverURL)
	if err != nil {
		t.Fatalf("failed to parse server URL: %v", err)
	}

	originalHostname := config.RabbitMQ.Hostname
	originalPort := config.RabbitMQ.ManagementPort
	originalUser := config.RabbitMQ.AdminUser
	originalPass := config.RabbitMQ.AdminPass

	config.RabbitMQ.Hostname = parsed.Hostname()
	config.RabbitMQ.ManagementPort = parsed.Port()
	config.RabbitMQ.AdminUser = user
	config.RabbitMQ.AdminPass = pass

	t.Cleanup(func() {
		config.RabbitMQ.Hostname = originalHostname
		config.RabbitMQ.ManagementPort = originalPort
		config.RabbitMQ.AdminUser = originalUser
		config.RabbitMQ.AdminPass = originalPass
	})
}

func testDTO() *models.InstanceDTO {
	return &models.InstanceDTO{
		Instance:            "instance-test",
		InstanceDisplayName: "Test Instance",
		RabbitPassword:      "rabbit-pass",
	}
}

func decodeBody(t *testing.T, request *http.Request) map[string]string {
	t.Helper()

	bodyBytes, err := io.ReadAll(request.Body)
	if err != nil {
		t.Fatalf("failed to read request body: %v", err)
	}

	if len(bodyBytes) == 0 {
		return nil
	}

	body := map[string]string{}
	if err := json.Unmarshal(bodyBytes, &body); err != nil {
		t.Fatalf("failed to decode request body: %v", err)
	}

	return body
}

func TestCreateVHostSuccess(t *testing.T) {
	requests := make([]capturedRequest, 0)
	server := httptest.NewServer(http.HandlerFunc(func(writer http.ResponseWriter, request *http.Request) {
		user, pass, _ := request.BasicAuth()
		requests = append(requests, capturedRequest{
			Method: request.Method,
			Path:   request.URL.Path,
			User:   user,
			Pass:   pass,
			Body:   decodeBody(t, request),
		})
		writer.WriteHeader(http.StatusCreated)
	}))
	defer server.Close()

	withRabbitConfig(t, server.URL, "admin", "secret")

	client := NewClient()
	ok, err := client.CreateVHost(testDTO())
	if err != nil {
		t.Fatalf("expected no error, got %v", err)
	}
	if !ok {
		t.Fatal("expected true result")
	}

	if len(requests) != 1 {
		t.Fatalf("expected one request, got %d", len(requests))
	}

	req := requests[0]
	if req.Method != http.MethodPut || req.Path != "/api/vhosts/instance-test" {
		t.Fatalf("unexpected request %s %s", req.Method, req.Path)
	}
	if req.User != "admin" || req.Pass != "secret" {
		t.Fatalf("unexpected credentials %s:%s", req.User, req.Pass)
	}
	if req.Body["description"] != "'ocInstanceDisplayName: Test Instance'" {
		t.Fatalf("unexpected payload: %v", req.Body)
	}
}

func TestCreateVHostFailure(t *testing.T) {
	server := httptest.NewServer(http.HandlerFunc(func(writer http.ResponseWriter, request *http.Request) {
		writer.WriteHeader(http.StatusInternalServerError)
	}))
	defer server.Close()

	withRabbitConfig(t, server.URL, "admin", "secret")

	client := NewClient()
	if _, err := client.CreateVHost(testDTO()); err == nil {
		t.Fatal("expected error")
	}
}

func TestCreateUserSuccess(t *testing.T) {
	requests := make([]capturedRequest, 0)
	server := httptest.NewServer(http.HandlerFunc(func(writer http.ResponseWriter, request *http.Request) {
		user, pass, _ := request.BasicAuth()
		requests = append(requests, capturedRequest{
			Method: request.Method,
			Path:   request.URL.Path,
			User:   user,
			Pass:   pass,
			Body:   decodeBody(t, request),
		})
		writer.WriteHeader(http.StatusCreated)
	}))
	defer server.Close()

	withRabbitConfig(t, server.URL, "admin", "secret")

	client := NewClient()
	ok, err := client.CreateUser(testDTO())
	if err != nil {
		t.Fatalf("expected no error, got %v", err)
	}
	if !ok {
		t.Fatal("expected true result")
	}

	if len(requests) != 1 {
		t.Fatalf("expected one request, got %d", len(requests))
	}

	req := requests[0]
	if req.Method != http.MethodPut || req.Path != "/api/users/instance-test" {
		t.Fatalf("unexpected request %s %s", req.Method, req.Path)
	}
	if req.Body["password"] != "rabbit-pass" || req.Body["tags"] != "monitoring" {
		t.Fatalf("unexpected payload: %v", req.Body)
	}
}

func TestCreateUserFailure(t *testing.T) {
	server := httptest.NewServer(http.HandlerFunc(func(writer http.ResponseWriter, request *http.Request) {
		writer.WriteHeader(http.StatusInternalServerError)
	}))
	defer server.Close()

	withRabbitConfig(t, server.URL, "admin", "secret")

	client := NewClient()
	if _, err := client.CreateUser(testDTO()); err == nil {
		t.Fatal("expected error")
	}
}

func TestSetPermissionsSuccess(t *testing.T) {
	requests := make([]capturedRequest, 0)
	server := httptest.NewServer(http.HandlerFunc(func(writer http.ResponseWriter, request *http.Request) {
		user, pass, _ := request.BasicAuth()
		requests = append(requests, capturedRequest{
			Method: request.Method,
			Path:   request.URL.Path,
			User:   user,
			Pass:   pass,
			Body:   decodeBody(t, request),
		})
		writer.WriteHeader(http.StatusCreated)
	}))
	defer server.Close()

	withRabbitConfig(t, server.URL, "admin", "secret")

	client := NewClient()
	ok, err := client.SetPermissions(testDTO())
	if err != nil {
		t.Fatalf("expected no error, got %v", err)
	}
	if !ok {
		t.Fatal("expected true result")
	}

	if len(requests) != 2 {
		t.Fatalf("expected two requests, got %d", len(requests))
	}

	if requests[0].Method != http.MethodPut || requests[0].Path != "/api/permissions/instance-test/instance-test" {
		t.Fatalf("unexpected first request %s %s", requests[0].Method, requests[0].Path)
	}
	if requests[1].Method != http.MethodPut || requests[1].Path != "/api/permissions/instance-test/admin" {
		t.Fatalf("unexpected second request %s %s", requests[1].Method, requests[1].Path)
	}

	for i, req := range requests {
		if req.User != "admin" || req.Pass != "secret" {
			t.Fatalf("unexpected credentials in request %d: %s:%s", i, req.User, req.Pass)
		}
		if req.Body["configure"] != ".*" || req.Body["write"] != ".*" || req.Body["read"] != ".*" {
			t.Fatalf("unexpected payload in request %d: %v", i, req.Body)
		}
	}
}

func TestSetPermissionsFirstRequestFailure(t *testing.T) {
	requestCount := 0
	server := httptest.NewServer(http.HandlerFunc(func(writer http.ResponseWriter, request *http.Request) {
		requestCount++
		writer.WriteHeader(http.StatusInternalServerError)
	}))
	defer server.Close()

	withRabbitConfig(t, server.URL, "admin", "secret")

	client := NewClient()
	if _, err := client.SetPermissions(testDTO()); err == nil {
		t.Fatal("expected error")
	}
	if requestCount != 1 {
		t.Fatalf("expected one request before failure, got %d", requestCount)
	}
}

func TestSetPermissionsSecondRequestFailure(t *testing.T) {
	requestCount := 0
	server := httptest.NewServer(http.HandlerFunc(func(writer http.ResponseWriter, request *http.Request) {
		requestCount++
		if requestCount == 1 {
			writer.WriteHeader(http.StatusCreated)
			return
		}
		writer.WriteHeader(http.StatusInternalServerError)
	}))
	defer server.Close()

	withRabbitConfig(t, server.URL, "admin", "secret")

	client := NewClient()
	if _, err := client.SetPermissions(testDTO()); err == nil {
		t.Fatal("expected error")
	}
	if requestCount != 2 {
		t.Fatalf("expected two requests, got %d", requestCount)
	}
}

func TestDeleteUserSuccess(t *testing.T) {
	requests := make([]capturedRequest, 0)
	server := httptest.NewServer(http.HandlerFunc(func(writer http.ResponseWriter, request *http.Request) {
		requests = append(requests, capturedRequest{Method: request.Method, Path: request.URL.Path})
		writer.WriteHeader(http.StatusNoContent)
	}))
	defer server.Close()

	withRabbitConfig(t, server.URL, "admin", "secret")

	client := NewClient()
	ok, err := client.DeleteUser("instance-test")
	if err != nil {
		t.Fatalf("expected no error, got %v", err)
	}
	if !ok {
		t.Fatal("expected true result")
	}

	if len(requests) != 1 {
		t.Fatalf("expected one request, got %d", len(requests))
	}
	if requests[0].Method != http.MethodDelete || requests[0].Path != "/api/users/instance-test" {
		t.Fatalf("unexpected request %s %s", requests[0].Method, requests[0].Path)
	}
}

func TestDeleteUserFailure(t *testing.T) {
	server := httptest.NewServer(http.HandlerFunc(func(writer http.ResponseWriter, request *http.Request) {
		writer.WriteHeader(http.StatusInternalServerError)
	}))
	defer server.Close()

	withRabbitConfig(t, server.URL, "admin", "secret")

	client := NewClient()
	if _, err := client.DeleteUser("instance-test"); err == nil {
		t.Fatal("expected error")
	}
}

func TestDeleteVHostSuccess(t *testing.T) {
	requests := make([]capturedRequest, 0)
	server := httptest.NewServer(http.HandlerFunc(func(writer http.ResponseWriter, request *http.Request) {
		requests = append(requests, capturedRequest{Method: request.Method, Path: request.URL.Path})
		writer.WriteHeader(http.StatusNoContent)
	}))
	defer server.Close()

	withRabbitConfig(t, server.URL, "admin", "secret")

	client := NewClient()
	ok, err := client.DeleteVHost("instance-test")
	if err != nil {
		t.Fatalf("expected no error, got %v", err)
	}
	if !ok {
		t.Fatal("expected true result")
	}

	if len(requests) != 1 {
		t.Fatalf("expected one request, got %d", len(requests))
	}
	if requests[0].Method != http.MethodDelete || requests[0].Path != "/api/vhosts/instance-test" {
		t.Fatalf("unexpected request %s %s", requests[0].Method, requests[0].Path)
	}
}

func TestDeleteVHostFailure(t *testing.T) {
	server := httptest.NewServer(http.HandlerFunc(func(writer http.ResponseWriter, request *http.Request) {
		writer.WriteHeader(http.StatusInternalServerError)
	}))
	defer server.Close()

	withRabbitConfig(t, server.URL, "admin", "secret")

	client := NewClient()
	if _, err := client.DeleteVHost("instance-test"); err == nil {
		t.Fatal("expected error")
	}
}

func TestHealthSuccess(t *testing.T) {
	server := httptest.NewServer(http.HandlerFunc(func(writer http.ResponseWriter, request *http.Request) {
		if request.Method != http.MethodGet {
			t.Fatalf("expected method %s, got %s", http.MethodGet, request.Method)
		}
		if request.URL.Path != "/api/overview" {
			t.Fatalf("expected path /api/overview, got %s", request.URL.Path)
		}

		user, pass, ok := request.BasicAuth()
		if !ok {
			t.Fatal("expected basic auth")
		}
		if user != "admin" || pass != "secret" {
			t.Fatalf("unexpected credentials %s:%s", user, pass)
		}

		writer.WriteHeader(http.StatusOK)
	}))
	defer server.Close()

	withRabbitConfig(t, server.URL, "admin", "secret")

	client := NewClient()
	if err := client.Health(); err != nil {
		t.Fatalf("expected no error, got %v", err)
	}
}

func TestHealthFailureStatusCode(t *testing.T) {
	server := httptest.NewServer(http.HandlerFunc(func(writer http.ResponseWriter, request *http.Request) {
		writer.WriteHeader(http.StatusInternalServerError)
	}))
	defer server.Close()

	withRabbitConfig(t, server.URL, "admin", "secret")

	client := NewClient()
	if err := client.Health(); err == nil {
		t.Fatal("expected error for non-2xx response")
	}
}
