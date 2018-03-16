package handler

import ws "clever-monitor.com/workflow/pkg/workflowservice"

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
	NotFound
)

type Handler interface {
	Handle(method string, in *ws.WorkflowRequest) *ws.WorkflowResponse
}