package server

import (
	"testing"
	ws "clever-monitor/workflow/pkg/workflowservice/clevermonitor/analytics/protos/workflow"
	"clever-monitor/utils/logger"
	"github.com/stretchr/testify/assert"
)

type handlerMock struct{}

func (h *handlerMock) HandleCreate(in *ws.CreateRequest) *ws.WorkflowResponse {
	return &ws.WorkflowResponse{Id: "0"}
}

func (h *handlerMock) HandleDelete(in *ws.DeleteRequest) *ws.WorkflowResponse {
	return &ws.WorkflowResponse{Id: "1"}
}

func (h *handlerMock) HandleReadEditorConfig(in *ws.ReadRequest) *ws.WorkflowResponse {
	return &ws.WorkflowResponse{Id: "2"}
}

func (h *handlerMock) HandleReadWorkflowConfig(in *ws.ReadRequest) *ws.WorkflowResponse {
	return &ws.WorkflowResponse{Id: "3"}
}

func (h *handlerMock) HandleReadAllWorkflowConfigs(in *ws.ReadAllRequest) *ws.WorkflowResponse {
	return &ws.WorkflowResponse{Id: "4"}
}


var s = NewServer("localhost:6060", &handlerMock{}, logger.GetNullLogger())

func TestServer_CreateWorkflow(t *testing.T) {
	response, err := s.CreateWorkflow(nil, &ws.CreateRequest{})

	assert.Nil(t, err)
	assert.Equal(t, "0", response.Id)
}

func TestServer_DeleteWorkflow(t *testing.T) {
	response, err := s.DeleteWorkflow(nil, &ws.DeleteRequest{})

	assert.Nil(t, err)
	assert.Equal(t, "1", response.Id)
}

func TestServer_ReadEditorConfig(t *testing.T) {
	response, err := s.ReadEditorConfig(nil, &ws.ReadRequest{})

	assert.Nil(t, err)
	assert.Equal(t, "2", response.Id)
}

//func TestServer_ReadWorkflowConfig(t *testing.T) {
//	conf, err := s.ReadWorkflowConfig(nil, &ws.ReadRequest{})
//
//	assert.Nil(t, err)
//	assert.Equal(t, "3", conf.Id)
//}
//
//func TestServer_ReadAllWorkflowConfigs(t *testing.T) {
//	conf, err := s.ReadAllWorkflowConfigs(nil, &ws.ReadAllRequest{})
//
//	assert.Nil(t, err)
//	assert.Equal(t, "4", conf.Id)
//}

