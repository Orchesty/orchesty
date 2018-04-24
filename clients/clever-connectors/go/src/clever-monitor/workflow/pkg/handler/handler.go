package handler

import ws "clever-monitor/workflow/pkg/workflowservice"

type ResponseCode int

const (
	OK             ResponseCode = iota
	InvalidRequest
	InternalError
	NotFound
)

type Handler interface {
	HandleCreate(in *ws.CreateRequest) *ws.WorkflowResponse
	HandleDelete(in *ws.DeleteRequest) *ws.WorkflowResponse

	ConfigReader
}

type ConfigReader interface {
	HandleReadEditorConfig(in *ws.ReadRequest) *ws.WorkflowResponse
	HandleReadWorkflowConfig(in *ws.ReadRequest) *ws.WorkflowResponse
	HandleReadAllWorkflowConfigs(in *ws.ReadAllRequest) *ws.WorkflowResponse
}
