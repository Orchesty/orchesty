package handler

import (
	"fmt"
	"clever-monitor.com/limiter/pkg/logger"
	"clever-monitor.com/workflow/pkg/storage"
	ws "clever-monitor.com/workflow/workflowservice"
)

type workflowHandler struct {
	storage storage.Storage
	logger  logger.Logger
}

func NewWorkflowHandler(storage storage.Storage, logger logger.Logger) *workflowHandler {
	return &workflowHandler{storage: storage, logger: logger}
}

func (wh *workflowHandler) Handle(method string, in *ws.WorkflowRequest) *ws.WorkflowResponse {
	wh.logger.Info(fmt.Sprintf("Handling workflow request '%s'.", method), logger.Context{})

	switch method {
	case HandleCreate:
		return wh.handleCreate(in)
	case HandleUpdate:
		return wh.handleUpdate(in)
	case HandleRead:
		return wh.handleRead(in)
	case HandleDelete:
		return wh.handleDelete(in)
	default:
		return &ws.WorkflowResponse{Code: 13, Message: fmt.Sprintf("Invalid method call '%s'", method)}
	}
}

// TODO - implement codes according to "google.golang.org/grpc/codes"

func (wh *workflowHandler) handleCreate(in *ws.WorkflowRequest) *ws.WorkflowResponse {
	id, err := wh.storage.Create(in.Json)
	if err != nil {
		return &ws.WorkflowResponse{Code: 13, Message: err.Error()}
	}

	return &ws.WorkflowResponse{Code: 0, Message: "OK", Id: id}
}

func (wh *workflowHandler) handleRead(in *ws.WorkflowRequest) *ws.WorkflowResponse {
	data, err := wh.storage.Find(in.Id)
	if err != nil {
		return &ws.WorkflowResponse{Code: 5, Message: err.Error()}
	}

	return &ws.WorkflowResponse{Code: 0, Message: "Workflow found", Id: in.Id, Json: data.(string)}
}

func (wh *workflowHandler) handleUpdate(in *ws.WorkflowRequest) *ws.WorkflowResponse {
	_, err := wh.storage.Update(in.Id, in.Json)
	if err != nil {
		return &ws.WorkflowResponse{Code: 13, Message: err.Error(), Id: in.Id}
	}

	return &ws.WorkflowResponse{Code: 0, Message: "Update OK", Id: in.Id}
}

func (wh *workflowHandler) handleDelete(in *ws.WorkflowRequest) *ws.WorkflowResponse {
	del, err := wh.storage.Delete(in.Id)
	if err != nil {
		return &ws.WorkflowResponse{Code: 13, Message: err.Error(), Id: in.Id}
	}

	if del != true {
		return &ws.WorkflowResponse{Code: 13, Message: fmt.Sprintf("Could not delete workflow '%s'.", in.Id), Id: in.Id}
	}

	return &ws.WorkflowResponse{Code: 0, Message: "Update OK", Id: in.Id}
}
