package handler

import (
	"testing"
	"github.com/stretchr/testify/assert"
	ws "clever-monitor/workflow/pkg/workflowservice"
	"fmt"
	"io/ioutil"
)

const (
	failureToken    = "failure"
	errorObjectId   = "aaaaaaaaaaaaaaaaaaaaaaaa"
	successObjectId = "5aa228e1922688649d414d84"
)

type storageMock struct{}

func (s *storageMock) Create(json string) (string, error) {
	if json == failureToken {
		return "", fmt.Errorf("storage create error")
	}

	return successObjectId, nil
}
func (s *storageMock) Update(id string, json string) (string, error) {
	if id == errorObjectId {
		return "", fmt.Errorf("storage update error")
	}

	return successObjectId, nil
}
func (s *storageMock) Find(id string) (string, error) {
	if id == errorObjectId {
		return "", fmt.Errorf("storage find error")
	}

	return "{\"foo\": \"bar\"}", nil
}
func (s *storageMock) Delete(id string) (error) {
	if id == errorObjectId {
		return fmt.Errorf("storage delete error")
	}

	return nil
}

func TestWorkflowHandler_Handle_InvalidMethod(t *testing.T) {
	handler := NewWorkflowHandler(&storageMock{})
	response := handler.Handle("foo", &ws.WorkflowRequest{})

	assert.Equal(t, int32(InvalidRequest), response.Code)
	assert.Equal(t, fmt.Sprintf(messageErrorMethod, "foo"), response.Message)
}

func TestWorkflowHandler_Handle_Create(t *testing.T) {
	handler := NewWorkflowHandler(&storageMock{})

	response := handler.Handle(HandleCreate, &ws.WorkflowRequest{})
	assert.Equal(t, int32(InvalidRequest), response.Code)
	assert.Equal(t, messageErrorJsonEmpty, response.Message)

	response = handler.Handle(HandleCreate, &ws.WorkflowRequest{Json: "{\"foo\": \"bar\"}"})
	assert.Equal(t, int32(InvalidRequest), response.Code)
	assert.Equal(t, messageErrorJsonInvalid, response.Message)

	b, err := ioutil.ReadFile("examples/example.json")
	assert.Nil(t, err)

	response = handler.Handle(HandleCreate, &ws.WorkflowRequest{Json: string(b)})
	assert.Equal(t, int32(OK), response.Code)
	assert.Equal(t, messageSuccessCreated, response.Message)
}

func TestWorkflowHandler_Handle_Read(t *testing.T) {
	handler := NewWorkflowHandler(&storageMock{})

	response := handler.Handle(HandleRead, &ws.WorkflowRequest{})
	assert.Equal(t, int32(InvalidRequest), response.Code)
	assert.Equal(t, messageErrorIdEmpty, response.Message)

	response = handler.Handle(HandleRead, &ws.WorkflowRequest{Id: "invalid-id"})
	assert.Equal(t, int32(InvalidRequest), response.Code)
	assert.Equal(t, messageErrorIdInvalid, response.Message)

	response = handler.Handle(HandleRead, &ws.WorkflowRequest{Id: errorObjectId})
	assert.Equal(t, int32(NotFound), response.Code)
	assert.Equal(t, "storage find error", response.Message)

	response = handler.Handle(HandleRead, &ws.WorkflowRequest{Id: successObjectId})
	assert.Equal(t, int32(OK), response.Code)
	assert.Equal(t, messageSuccessFound, response.Message)
}

func TestWorkflowHandler_Handle_Update(t *testing.T) {
	handler := NewWorkflowHandler(&storageMock{})

	response := handler.Handle(HandleUpdate, &ws.WorkflowRequest{})
	assert.Equal(t, int32(InvalidRequest), response.Code)
	assert.Equal(t, messageErrorIdEmpty, response.Message)

	response = handler.Handle(HandleUpdate, &ws.WorkflowRequest{Id: "invalid-id"})
	assert.Equal(t, int32(InvalidRequest), response.Code)
	assert.Equal(t, messageErrorIdInvalid, response.Message)

	response = handler.Handle(HandleUpdate, &ws.WorkflowRequest{Id: successObjectId, Json: "{\"foo\": \"bar\"}"})
	assert.Equal(t, int32(InvalidRequest), response.Code)
	assert.Equal(t, messageErrorJsonInvalid, response.Message)

	b, err := ioutil.ReadFile("examples/example.json")
	assert.Nil(t, err)

	response = handler.Handle(HandleUpdate, &ws.WorkflowRequest{Id: errorObjectId, Json: string(b)})
	assert.Equal(t, int32(InternalError), response.Code)
	assert.Equal(t, "storage update error", response.Message)

	response = handler.Handle(HandleUpdate, &ws.WorkflowRequest{Id: successObjectId, Json: string(b)})
	assert.Equal(t, int32(OK), response.Code)
	assert.Equal(t, messageSuccessUpdated, response.Message)
}

func TestWorkflowHandler_Handle_Delete(t *testing.T) {
	handler := NewWorkflowHandler(&storageMock{})

	response := handler.Handle(HandleDelete, &ws.WorkflowRequest{})
	assert.Equal(t, int32(InvalidRequest), response.Code)
	assert.Equal(t, messageErrorIdEmpty, response.Message)

	response = handler.Handle(HandleDelete, &ws.WorkflowRequest{Id: "invalid-id"})
	assert.Equal(t, int32(InvalidRequest), response.Code)
	assert.Equal(t, messageErrorIdInvalid, response.Message)

	response = handler.Handle(HandleDelete, &ws.WorkflowRequest{Id: errorObjectId})
	assert.Equal(t, int32(NotFound), response.Code)
	assert.Equal(t, "storage delete error", response.Message)

	response = handler.Handle(HandleDelete, &ws.WorkflowRequest{Id: successObjectId})
	assert.Equal(t, int32(OK), response.Code)
	assert.Equal(t, messageSuccessDeleted, response.Message)
}
