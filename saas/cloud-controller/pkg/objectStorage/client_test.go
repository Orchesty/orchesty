package objectStorage

import (
	"encoding/json"
	"io"
	"net/http"
	"net/http/httptest"
	"strings"
	"testing"

	"cloud-controller/pkg/config"
	"cloud-controller/pkg/models"
)

type capturedRequest struct {
	Method string
	Path   string
	Body   map[string]string
}

func withGCSConfig(t *testing.T, serverURL string) {
	t.Helper()

	originalEnabled := config.GCS.Enabled
	originalProjectID := config.GCS.ProjectID
	originalLocation := config.GCS.Location
	originalEndpoint := config.GCS.Endpoint
	originalSAEmail := config.GCS.ServiceAccountEmail

	config.GCS.Enabled = true
	config.GCS.ProjectID = "test-project"
	config.GCS.Location = "eu"
	config.GCS.Endpoint = serverURL + "/storage/v1/"
	config.GCS.ServiceAccountEmail = "test@test-project.iam.gserviceaccount.com"

	t.Cleanup(func() {
		config.GCS.Enabled = originalEnabled
		config.GCS.ProjectID = originalProjectID
		config.GCS.Location = originalLocation
		config.GCS.Endpoint = originalEndpoint
		config.GCS.ServiceAccountEmail = originalSAEmail
	})
}

func testDTO(logsEnabled bool) *models.InstanceDTO {
	return &models.InstanceDTO{
		Instance:   "instance-abc123",
		InstanceId: "abc123",
		Customizations: models.Customizations{
			Logs: models.Logs{
				Enabled: logsEnabled,
			},
		},
	}
}

func TestCreateBucketSuccess(t *testing.T) {
	requests := make([]capturedRequest, 0)
	server := httptest.NewServer(http.HandlerFunc(func(writer http.ResponseWriter, request *http.Request) {
		bodyBytes, _ := io.ReadAll(request.Body)
		body := map[string]string{}
		_ = json.Unmarshal(bodyBytes, &body)
		requests = append(requests, capturedRequest{
			Method: request.Method,
			Path:   request.URL.Path,
			Body:   body,
		})

		if request.Method == http.MethodPost && strings.Contains(request.URL.Path, "hmacKeys") {
			writer.WriteHeader(http.StatusOK)
			_, _ = writer.Write([]byte(`{"metadata":{"accessId":"GOOG1ETEST123"},"secret":"s3cr3tK3y"}`))
			return
		}

		writer.WriteHeader(http.StatusOK)
		_, _ = writer.Write([]byte(`{"name":"logs-instance-abc123"}`))
	}))
	defer server.Close()

	withGCSConfig(t, server.URL)

	client := NewClient()
	creds, err := client.CreateBucket(testDTO(true))
	if err != nil {
		t.Fatalf("expected no error, got %v", err)
	}

	if creds == nil {
		t.Fatal("expected HMAC credentials, got nil")
	}

	if creds.AccessKey != "GOOG1ETEST123" {
		t.Fatalf("expected access key GOOG1ETEST123, got %s", creds.AccessKey)
	}

	if creds.SecretKey != "s3cr3tK3y" {
		t.Fatalf("expected secret key s3cr3tK3y, got %s", creds.SecretKey)
	}

	if len(requests) != 2 {
		t.Fatalf("expected 2 requests (create bucket + create HMAC), got %d", len(requests))
	}

	if requests[0].Method != http.MethodPost {
		t.Fatalf("expected POST for bucket, got %s", requests[0].Method)
	}

	if requests[0].Body["name"] != "logs-instance-abc123" {
		t.Fatalf("expected bucket name logs-instance-abc123, got %v", requests[0].Body["name"])
	}

	if requests[1].Method != http.MethodPost {
		t.Fatalf("expected POST for HMAC, got %s", requests[1].Method)
	}
}

func TestCreateBucketAPIError(t *testing.T) {
	server := httptest.NewServer(http.HandlerFunc(func(writer http.ResponseWriter, _ *http.Request) {
		writer.WriteHeader(http.StatusForbidden)
		_, _ = writer.Write([]byte(`{"error":"forbidden"}`))
	}))
	defer server.Close()

	withGCSConfig(t, server.URL)

	client := NewClient()
	creds, err := client.CreateBucket(testDTO(true))
	if err == nil {
		t.Fatal("expected error")
	}
	if creds != nil {
		t.Fatal("expected nil credentials on error")
	}
}

func TestDeleteBucketSuccess(t *testing.T) {
	requests := make([]capturedRequest, 0)
	server := httptest.NewServer(http.HandlerFunc(func(writer http.ResponseWriter, request *http.Request) {
		requests = append(requests, capturedRequest{
			Method: request.Method,
			Path:   request.URL.Path,
		})

		if request.Method == http.MethodGet && request.URL.Path == "/storage/v1/b/logs-instance-abc123/o" {
			writer.WriteHeader(http.StatusOK)
			_, _ = writer.Write([]byte(`{"items":[]}`))
			return
		}

		if request.Method == http.MethodDelete {
			writer.WriteHeader(http.StatusNoContent)
			return
		}

		writer.WriteHeader(http.StatusOK)
	}))
	defer server.Close()

	withGCSConfig(t, server.URL)

	client := NewClient()
	if err := client.DeleteBucket("instance-abc123"); err != nil {
		t.Fatalf("expected no error, got %v", err)
	}

	if len(requests) != 2 {
		t.Fatalf("expected 2 requests (list objects + delete bucket), got %d: %v", len(requests), requests)
	}

	if requests[0].Method != http.MethodGet {
		t.Fatalf("expected GET for list objects, got %s", requests[0].Method)
	}

	if requests[1].Method != http.MethodDelete {
		t.Fatalf("expected DELETE for bucket, got %s", requests[1].Method)
	}
}

func TestDeleteBucketNotFound(t *testing.T) {
	server := httptest.NewServer(http.HandlerFunc(func(writer http.ResponseWriter, request *http.Request) {
		writer.WriteHeader(http.StatusNotFound)
	}))
	defer server.Close()

	withGCSConfig(t, server.URL)

	client := NewClient()
	if err := client.DeleteBucket("instance-abc123"); err != nil {
		t.Fatalf("expected no error for missing bucket, got %v", err)
	}
}

func TestUpdateBucketLogsEnabled(t *testing.T) {
	createCalled := false
	server := httptest.NewServer(http.HandlerFunc(func(writer http.ResponseWriter, request *http.Request) {
		if request.Method == http.MethodPost && !strings.Contains(request.URL.Path, "hmacKeys") {
			createCalled = true
		}

		if request.Method == http.MethodPost && strings.Contains(request.URL.Path, "hmacKeys") {
			writer.WriteHeader(http.StatusOK)
			_, _ = writer.Write([]byte(`{"metadata":{"accessId":"GOOG1EUPDATE"},"secret":"updateSecret"}`))
			return
		}

		writer.WriteHeader(http.StatusOK)
		_, _ = writer.Write([]byte(`{"name":"logs-instance-abc123"}`))
	}))
	defer server.Close()

	withGCSConfig(t, server.URL)

	client := NewClient()
	creds, err := client.UpdateBucket(testDTO(true))
	if err != nil {
		t.Fatalf("expected no error, got %v", err)
	}

	if !createCalled {
		t.Fatal("expected CreateBucket to be called when logs enabled")
	}

	if creds == nil {
		t.Fatal("expected HMAC credentials when logs enabled")
	}
}

func TestUpdateBucketLogsDisabled(t *testing.T) {
	deleteCalled := false
	server := httptest.NewServer(http.HandlerFunc(func(writer http.ResponseWriter, request *http.Request) {
		if request.Method == http.MethodDelete {
			deleteCalled = true
			writer.WriteHeader(http.StatusNoContent)
			return
		}
		if request.Method == http.MethodGet {
			writer.WriteHeader(http.StatusNotFound)
			return
		}
		writer.WriteHeader(http.StatusOK)
	}))
	defer server.Close()

	withGCSConfig(t, server.URL)

	client := NewClient()
	creds, err := client.UpdateBucket(testDTO(false))
	if err != nil {
		t.Fatalf("expected no error, got %v", err)
	}

	if !deleteCalled {
		t.Fatal("expected DeleteBucket to be called when logs disabled")
	}

	if creds != nil {
		t.Fatal("expected nil credentials when logs disabled")
	}
}

func TestHealthSuccess(t *testing.T) {
	server := httptest.NewServer(http.HandlerFunc(func(writer http.ResponseWriter, _ *http.Request) {
		writer.WriteHeader(http.StatusOK)
		_, _ = writer.Write([]byte(`{"items":[]}`))
	}))
	defer server.Close()

	withGCSConfig(t, server.URL)

	client := NewClient()
	if err := client.Health(); err != nil {
		t.Fatalf("expected no error, got %v", err)
	}
}

func TestHealthError(t *testing.T) {
	server := httptest.NewServer(http.HandlerFunc(func(writer http.ResponseWriter, _ *http.Request) {
		writer.WriteHeader(http.StatusServiceUnavailable)
		_, _ = writer.Write([]byte(`{"error":"unavailable"}`))
	}))
	defer server.Close()

	withGCSConfig(t, server.URL)

	client := NewClient()
	err := client.Health()
	if err == nil {
		t.Fatal("expected error")
	}
}

func TestCreateBucketConflictReturnsNilCreds(t *testing.T) {
	server := httptest.NewServer(http.HandlerFunc(func(writer http.ResponseWriter, _ *http.Request) {
		writer.WriteHeader(http.StatusConflict)
		_, _ = writer.Write([]byte(`{"error":"bucket already exists"}`))
	}))
	defer server.Close()

	withGCSConfig(t, server.URL)

	client := NewClient()
	creds, err := client.CreateBucket(testDTO(true))
	if err != nil {
		t.Fatalf("expected no error on conflict, got %v", err)
	}
	if creds != nil {
		t.Fatal("expected nil credentials on conflict")
	}
}

func TestDeleteHMACKeySuccess(t *testing.T) {
	requests := make([]capturedRequest, 0)
	server := httptest.NewServer(http.HandlerFunc(func(writer http.ResponseWriter, request *http.Request) {
		requests = append(requests, capturedRequest{
			Method: request.Method,
			Path:   request.URL.Path,
		})
		writer.WriteHeader(http.StatusOK)
		_, _ = writer.Write([]byte(`{}`))
	}))
	defer server.Close()

	withGCSConfig(t, server.URL)

	client := NewClient()
	if err := client.DeleteHMACKey("GOOG1ETEST123"); err != nil {
		t.Fatalf("expected no error, got %v", err)
	}

	if len(requests) != 2 {
		t.Fatalf("expected 2 requests (deactivate + delete), got %d", len(requests))
	}

	if requests[0].Method != http.MethodPut {
		t.Fatalf("expected PUT for deactivate, got %s", requests[0].Method)
	}

	if requests[1].Method != http.MethodDelete {
		t.Fatalf("expected DELETE for remove, got %s", requests[1].Method)
	}

	if !strings.Contains(requests[0].Path, "GOOG1ETEST123") {
		t.Fatalf("expected path to contain access key ID, got %s", requests[0].Path)
	}
}

func TestDeleteHMACKeyEmptyIdNoOp(t *testing.T) {
	server := httptest.NewServer(http.HandlerFunc(func(writer http.ResponseWriter, _ *http.Request) {
		t.Fatal("no request should be made for empty key ID")
	}))
	defer server.Close()

	withGCSConfig(t, server.URL)

	client := NewClient()
	if err := client.DeleteHMACKey(""); err != nil {
		t.Fatalf("expected no error for empty key, got %v", err)
	}
}

func TestDeleteHMACKeyNotFound(t *testing.T) {
	server := httptest.NewServer(http.HandlerFunc(func(writer http.ResponseWriter, _ *http.Request) {
		writer.WriteHeader(http.StatusNotFound)
	}))
	defer server.Close()

	withGCSConfig(t, server.URL)

	client := NewClient()
	if err := client.DeleteHMACKey("nonexistent"); err != nil {
		t.Fatalf("expected no error for not-found key, got %v", err)
	}
}
