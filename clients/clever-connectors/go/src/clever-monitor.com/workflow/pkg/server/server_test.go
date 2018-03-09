package server

import "testing"
import (
	ws "clever-monitor.com/workflow/workflowservice"
	"clever-monitor.com/limiter/pkg/logger"
	"github.com/stretchr/testify/assert"
	"clever-monitor.com/workflow/pkg/handler"
)

type handlerMock struct{}

// Handle mock retursn the called method in response ID field
func (h *handlerMock) Handle(method string, in *ws.WorkflowRequest) *ws.WorkflowResponse {
	return &ws.WorkflowResponse{Id: method}
}

var s = NewServer("localhost:6060", &handlerMock{}, logger.GetNullLogger())

func TestServer_CreateWorkflow(t *testing.T) {
	response, err := s.CreateWorkflow(nil, &ws.WorkflowRequest{})

	assert.Nil(t, err)
	assert.Equal(t, handler.HandleCreate, response.Id)
}

func TestServer_UpdateWorkflow(t *testing.T) {
	response, err := s.UpdateWorkflow(nil, &ws.WorkflowRequest{})

	assert.Nil(t, err)
	assert.Equal(t, handler.HandleUpdate, response.Id)
}

func TestServer_ReadWorkflow(t *testing.T) {
	response, err := s.ReadWorkflow(nil, &ws.WorkflowRequest{})

	assert.Nil(t, err)
	assert.Equal(t, handler.HandleRead, response.Id)
}

func TestServer_DeleteWorkflow(t *testing.T) {
	response, err := s.DeleteWorkflow(nil, &ws.WorkflowRequest{})

	assert.Nil(t, err)
	assert.Equal(t, handler.HandleDelete, response.Id)
}

func TestServer_ReadConfig(t *testing.T) {
	_, err := s.ReadConfig(nil, &ws.WorkflowRequest{})

	assert.Nil(t, err)
}

