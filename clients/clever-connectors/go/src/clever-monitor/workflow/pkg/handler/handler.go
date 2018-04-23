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
	HandleUpdate(in *ws.UpdateRequest) *ws.WorkflowResponse
	HandleDelete(in *ws.DeleteRequest) *ws.WorkflowResponse

	HandleReadEditorConfig(in *ws.ReadRequest) *ws.WorkflowResponse
	HandleReadWorkflowConfig(in *ws.ReadRequest) *ws.WorkflowResponse
}
