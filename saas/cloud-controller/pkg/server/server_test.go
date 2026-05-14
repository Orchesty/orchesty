package server

import (
	"bytes"
	"encoding/json"
	"errors"
	"net/http"
	"net/http/httptest"
	"testing"

	"cloud-controller/pkg/models"
	"cloud-controller/pkg/service"
)

type healthStub struct {
	err error
}

func (h healthStub) Ping() error {
	return h.err
}

func (h healthStub) Health() error {
	return h.err
}

type instanceServiceStub struct {
	createResult  models.InstanceInfo
	updateResult  models.InstanceInfo
	createErr     error
	updateErr     error
	deleteErr     error
	suspendErr    error
	resumeErr     error
	createCalls   int
	updateCalls   int
	deleteCalls   int
	suspendCalls  int
	resumeCalls   int
	createRequest service.CreateInstanceRequest
	updateRequest service.UpdateInstanceRequest
	deleteValue   string
	suspendValue  string
	resumeValue   string
}

func (s *instanceServiceStub) CreateInstance(request service.CreateInstanceRequest) (models.InstanceInfo, error) {
	s.createCalls++
	s.createRequest = request
	return s.createResult, s.createErr
}

func (s *instanceServiceStub) DeleteInstance(instance string) error {
	s.deleteCalls++
	s.deleteValue = instance
	return s.deleteErr
}

func (s *instanceServiceStub) UpdateInstance(request service.UpdateInstanceRequest) (models.InstanceInfo, error) {
	s.updateCalls++
	s.updateRequest = request
	return s.updateResult, s.updateErr
}

func (s *instanceServiceStub) SuspendInstance(instance string) error {
	s.suspendCalls++
	s.suspendValue = instance
	return s.suspendErr
}

func (s *instanceServiceStub) ResumeInstance(instance string) error {
	s.resumeCalls++
	s.resumeValue = instance
	return s.resumeErr
}

func TestStatusAllDependenciesHealthy(t *testing.T) {
	handler := New(nil, healthStub{}, healthStub{}, healthStub{}, healthStub{}, healthStub{})

	request := httptest.NewRequest(http.MethodGet, "/status", nil)
	response := httptest.NewRecorder()

	handler.ServeHTTP(response, request)

	if response.Code != http.StatusOK {
		t.Fatalf("expected status %d, got %d", http.StatusOK, response.Code)
	}

	var payload map[string]any
	if err := json.Unmarshal(response.Body.Bytes(), &payload); err != nil {
		t.Fatalf("failed to decode response: %v", err)
	}

	if payload["status"] != "ok" {
		t.Fatalf("expected status ok, got %v", payload["status"])
	}

	checks, ok := payload["checks"].(map[string]any)
	if !ok {
		t.Fatalf("expected checks map in payload, got %T", payload["checks"])
	}

	if checks["rabbitmq"] != "ok" || checks["mongodb"] != "ok" || checks["k8s"] != "ok" {
		t.Fatalf("expected all checks ok, got %v", checks)
	}
}

func TestStatusDependencyFailure(t *testing.T) {
	handler := New(nil, healthStub{}, healthStub{err: errors.New("rabbit unavailable")}, healthStub{}, healthStub{}, healthStub{})

	request := httptest.NewRequest(http.MethodGet, "/status", nil)
	response := httptest.NewRecorder()

	handler.ServeHTTP(response, request)

	if response.Code != http.StatusServiceUnavailable {
		t.Fatalf("expected status %d, got %d", http.StatusServiceUnavailable, response.Code)
	}

	var payload map[string]any
	if err := json.Unmarshal(response.Body.Bytes(), &payload); err != nil {
		t.Fatalf("failed to decode response: %v", err)
	}

	if payload["status"] != "error" {
		t.Fatalf("expected status error, got %v", payload["status"])
	}

	checks, ok := payload["checks"].(map[string]any)
	if !ok {
		t.Fatalf("expected checks map in payload, got %T", payload["checks"])
	}

	if checks["rabbitmq"] != "error" {
		t.Fatalf("expected rabbitmq error check, got %v", checks["rabbitmq"])
	}
}

func TestCreateInstanceSuccess(t *testing.T) {
	serviceStub := &instanceServiceStub{createResult: models.InstanceInfo{
		Instance:            "instance-test",
		InstanceDisplayName: "Test Instance",
		UserName:            "orchesty@hanaboso.com",
		UserPassword:        "secret",
	}}
	handler := New(serviceStub, healthStub{}, healthStub{}, healthStub{}, healthStub{}, healthStub{})

	request := httptest.NewRequest(http.MethodPost, "/instance", bytes.NewBufferString(`{"instanceInfo":{"instanceDisplayName":"Test Instance","instanceUrlPrefix":"test"},"instanceCredentials":{"instanceId":"aa","instanceSecret":"bbb"},"customizations":{"userName":"user@test.local","workers":[{"name":"default","image":"img","sdkType":"nodejs"}]}}`))
	request.Header.Set("Content-Type", "application/json")
	response := httptest.NewRecorder()

	handler.ServeHTTP(response, request)

	if response.Code != http.StatusCreated {
		t.Fatalf("expected status %d, got %d", http.StatusCreated, response.Code)
	}
	if serviceStub.createCalls != 1 {
		t.Fatalf("expected one create call, got %d", serviceStub.createCalls)
	}
	if serviceStub.createRequest.InstanceInfo.InstanceDisplayName != "Test Instance" {
		t.Fatalf("unexpected instanceDisplayName %q", serviceStub.createRequest.InstanceInfo.InstanceDisplayName)
	}
	if serviceStub.createRequest.Customizations.UserName != "user@test.local" {
		t.Fatalf("unexpected userName %q", serviceStub.createRequest.Customizations.UserName)
	}
	if len(serviceStub.createRequest.Customizations.Workers) != 1 || serviceStub.createRequest.Customizations.Workers[0].Image != "img" {
		t.Fatalf("unexpected workerImage %+v", serviceStub.createRequest.Customizations)
	}

	var payload models.InstanceInfo
	if err := json.Unmarshal(response.Body.Bytes(), &payload); err != nil {
		t.Fatalf("failed to decode response: %v", err)
	}
	if payload.Instance != "instance-test" {
		t.Fatalf("unexpected response payload %+v", payload)
	}
}

func TestCreateInstanceBadRequest(t *testing.T) {
	handler := New(&instanceServiceStub{}, healthStub{}, healthStub{}, healthStub{}, healthStub{}, healthStub{})

	request := httptest.NewRequest(http.MethodPost, "/instance", bytes.NewBufferString(`{"instanceDisplayName":`))
	response := httptest.NewRecorder()

	handler.ServeHTTP(response, request)

	if response.Code != http.StatusBadRequest {
		t.Fatalf("expected status %d, got %d", http.StatusBadRequest, response.Code)
	}
}

func TestCreateInstanceUnknownFieldBadRequest(t *testing.T) {
	handler := New(&instanceServiceStub{}, healthStub{}, healthStub{}, healthStub{}, healthStub{}, healthStub{})

	request := httptest.NewRequest(http.MethodPost, "/instance", bytes.NewBufferString(`{"instanceInfo":{"instanceDisplayName":"Test Instance","instanceUrlPrefix":"test"},"unexpected":true}`))
	response := httptest.NewRecorder()

	handler.ServeHTTP(response, request)

	if response.Code != http.StatusBadRequest {
		t.Fatalf("expected status %d, got %d", http.StatusBadRequest, response.Code)
	}
}

func TestCreateInstanceConflict(t *testing.T) {
	serviceStub := &instanceServiceStub{createErr: service.ErrInstanceUnavailable}
	handler := New(serviceStub, healthStub{}, healthStub{}, healthStub{}, healthStub{}, healthStub{})

	request := httptest.NewRequest(http.MethodPost, "/instance", bytes.NewBufferString(`{"instanceInfo":{"instanceDisplayName":"Test Instance","instanceUrlPrefix":"test"}}`))
	response := httptest.NewRecorder()

	handler.ServeHTTP(response, request)

	if response.Code != http.StatusConflict {
		t.Fatalf("expected status %d, got %d", http.StatusConflict, response.Code)
	}
}

func TestCreateInstanceInternalError(t *testing.T) {
	serviceStub := &instanceServiceStub{createErr: errors.New("create failed")}
	handler := New(serviceStub, healthStub{}, healthStub{}, healthStub{}, healthStub{}, healthStub{})

	request := httptest.NewRequest(http.MethodPost, "/instance", bytes.NewBufferString(`{"instanceInfo":{"instanceDisplayName":"Test Instance","instanceUrlPrefix":"test"}}`))
	response := httptest.NewRecorder()

	handler.ServeHTTP(response, request)

	if response.Code != http.StatusInternalServerError {
		t.Fatalf("expected status %d, got %d", http.StatusInternalServerError, response.Code)
	}
}

func TestDeleteInstanceSuccess(t *testing.T) {
	serviceStub := &instanceServiceStub{}
	handler := New(serviceStub, healthStub{}, healthStub{}, healthStub{}, healthStub{}, healthStub{})

	request := httptest.NewRequest(http.MethodDelete, "/instance?instance=instance-test", nil)
	response := httptest.NewRecorder()

	handler.ServeHTTP(response, request)

	if response.Code != http.StatusNoContent {
		t.Fatalf("expected status %d, got %d", http.StatusNoContent, response.Code)
	}
	if serviceStub.deleteCalls != 1 {
		t.Fatalf("expected one delete call, got %d", serviceStub.deleteCalls)
	}
	if serviceStub.deleteValue != "instance-test" {
		t.Fatalf("unexpected delete value %q", serviceStub.deleteValue)
	}
}

func TestDeleteInstanceBadRequest(t *testing.T) {
	serviceStub := &instanceServiceStub{deleteErr: &service.InputError{Err: service.ErrInstanceRequired}}
	handler := New(serviceStub, healthStub{}, healthStub{}, healthStub{}, healthStub{}, healthStub{})

	request := httptest.NewRequest(http.MethodDelete, "/instance", nil)
	response := httptest.NewRecorder()

	handler.ServeHTTP(response, request)

	if response.Code != http.StatusBadRequest {
		t.Fatalf("expected status %d, got %d", http.StatusBadRequest, response.Code)
	}
}

func TestDeleteInstanceInternalError(t *testing.T) {
	serviceStub := &instanceServiceStub{deleteErr: errors.New("delete failed")}
	handler := New(serviceStub, healthStub{}, healthStub{}, healthStub{}, healthStub{}, healthStub{})

	request := httptest.NewRequest(http.MethodDelete, "/instance?instance=instance-test", nil)
	response := httptest.NewRecorder()

	handler.ServeHTTP(response, request)

	if response.Code != http.StatusInternalServerError {
		t.Fatalf("expected status %d, got %d", http.StatusInternalServerError, response.Code)
	}
}

func TestUpdateInstanceSuccess(t *testing.T) {
	serviceStub := &instanceServiceStub{updateResult: models.InstanceInfo{
		Instance:            "instance-test",
		InstanceDisplayName: "New Name",
		UserName:            "admin@example.com",
		UserPassword:        "secret",
	}}
	handler := New(serviceStub, healthStub{}, healthStub{}, healthStub{}, healthStub{}, healthStub{})

	request := httptest.NewRequest(http.MethodPatch, "/instance", bytes.NewBufferString(`{"instance":"instance-test","instanceDisplayName":"New Name","customizations":{"workers":[{"name":"default","image":"img:v2","sdkType":"nodejs"}]}}`))
	request.Header.Set("Content-Type", "application/json")
	response := httptest.NewRecorder()

	handler.ServeHTTP(response, request)

	if response.Code != http.StatusOK {
		t.Fatalf("expected status %d, got %d", http.StatusOK, response.Code)
	}
	if serviceStub.updateCalls != 1 {
		t.Fatalf("expected one update call, got %d", serviceStub.updateCalls)
	}
	if serviceStub.updateRequest.Instance != "instance-test" {
		t.Fatalf("unexpected instance %q", serviceStub.updateRequest.Instance)
	}
	if serviceStub.updateRequest.InstanceDisplayName == nil || *serviceStub.updateRequest.InstanceDisplayName != "New Name" {
		t.Fatal("expected instanceDisplayName in update request")
	}
	if serviceStub.updateRequest.Customizations == nil || len(serviceStub.updateRequest.Customizations.Workers) != 1 || serviceStub.updateRequest.Customizations.Workers[0].Image != "img:v2" {
		t.Fatal("expected customizations in update request")
	}
}

func TestUpdateInstanceBadRequest(t *testing.T) {
	handler := New(&instanceServiceStub{}, healthStub{}, healthStub{}, healthStub{}, healthStub{}, healthStub{})

	request := httptest.NewRequest(http.MethodPatch, "/instance", bytes.NewBufferString(`{"instance":`))
	response := httptest.NewRecorder()

	handler.ServeHTTP(response, request)

	if response.Code != http.StatusBadRequest {
		t.Fatalf("expected status %d, got %d", http.StatusBadRequest, response.Code)
	}
}

func TestUpdateInstanceValidationError(t *testing.T) {
	serviceStub := &instanceServiceStub{updateErr: &service.InputError{Err: service.ErrInstanceRequired}}
	handler := New(serviceStub, healthStub{}, healthStub{}, healthStub{}, healthStub{}, healthStub{})

	request := httptest.NewRequest(http.MethodPatch, "/instance", bytes.NewBufferString(`{"instance":""}`))
	response := httptest.NewRecorder()

	handler.ServeHTTP(response, request)

	if response.Code != http.StatusBadRequest {
		t.Fatalf("expected status %d, got %d", http.StatusBadRequest, response.Code)
	}
}

func TestUpdateInstanceInternalError(t *testing.T) {
	serviceStub := &instanceServiceStub{updateErr: errors.New("update failed")}
	handler := New(serviceStub, healthStub{}, healthStub{}, healthStub{}, healthStub{}, healthStub{})

	request := httptest.NewRequest(http.MethodPatch, "/instance", bytes.NewBufferString(`{"instance":"instance-test"}`))
	response := httptest.NewRecorder()

	handler.ServeHTTP(response, request)

	if response.Code != http.StatusInternalServerError {
		t.Fatalf("expected status %d, got %d", http.StatusInternalServerError, response.Code)
	}
}
