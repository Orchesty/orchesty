package handler

import ws "clever-monitor.com/workflow/workflowservice"

const (
	HandleCreate = "create"
	HandleUpdate = "update"
	HandleRead   = "read"
	HandleDelete = "delete"
)

type ResponseCode int

const (
	OK ResponseCode = iota
	InvalidRequest
	InternalError
)

type Handler interface {
	Handle(method string, in *ws.WorkflowRequest) *ws.WorkflowResponse
}