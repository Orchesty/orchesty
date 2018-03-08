package handler

import (
	"fmt"
	"clever-monitor.com/limiter/pkg/logger"
	"clever-monitor.com/workflow/pkg/storage"
	ws "clever-monitor.com/workflow/workflowservice"
	"gopkg.in/mgo.v2/bson"
)

type workflowHandler struct {
	storage storage.Storage
	logger  logger.Logger
}

func NewWorkflowHandler(storage storage.Storage, logger logger.Logger) *workflowHandler {
	return &workflowHandler{storage: storage, logger: logger}
}

// Handle processes the workflow request
func (wh *workflowHandler) Handle(method string, in *ws.WorkflowRequest) *ws.WorkflowResponse {
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

// handleCreate tries to create new workflow record
func (wh *workflowHandler) handleCreate(in *ws.WorkflowRequest) *ws.WorkflowResponse {
	err := wh.validateJson(in.Json)
	if err != nil {
		return &ws.WorkflowResponse{Code: int32(InvalidRequest), Message: err.Error()}
	}

	id, err := wh.storage.Create(in.Json)
	if err != nil {
		return &ws.WorkflowResponse{Code: int32(InternalError), Message: err.Error()}
	}

	return &ws.WorkflowResponse{Code: int32(OK), Message: "OK", Id: id}
}

// handleRead tries to find and return the content of record by it's id
func (wh *workflowHandler) handleRead(in *ws.WorkflowRequest) *ws.WorkflowResponse {
	err := wh.validateId(in.Id)
	if err != nil {
		return &ws.WorkflowResponse{Code: int32(InvalidRequest), Message: err.Error()}
	}

	data, err := wh.storage.Find(in.Id)
	if err != nil {
		return &ws.WorkflowResponse{Code: int32(InternalError), Message: err.Error()}
	}

	return &ws.WorkflowResponse{Code: 0, Message: "Workflow found", Id: in.Id, Json: data.(string)}
}

// handleUpdate set's new content to storage for given id
func (wh *workflowHandler) handleUpdate(in *ws.WorkflowRequest) *ws.WorkflowResponse {
	err := wh.validateRequest(in)
	if err != nil {
		return &ws.WorkflowResponse{Code: int32(InvalidRequest), Message: err.Error()}
	}

	_, err = wh.storage.Update(in.Id, in.Json)
	if err != nil {
		return &ws.WorkflowResponse{Code: 13, Message: err.Error(), Id: in.Id}
	}

	return &ws.WorkflowResponse{Code: 0, Message: "Update OK", Id: in.Id}
}

// handleDelete removes the record from storage by it's id
func (wh *workflowHandler) handleDelete(in *ws.WorkflowRequest) *ws.WorkflowResponse {
	err := wh.validateId(in.Id)
	if err != nil {
		return &ws.WorkflowResponse{Code: int32(InvalidRequest), Message: err.Error()}
	}

	del, err := wh.storage.Delete(in.Id)
	if err != nil {
		return &ws.WorkflowResponse{Code: 13, Message: err.Error(), Id: in.Id}
	}

	if del != true {
		return &ws.WorkflowResponse{Code: 13, Message: fmt.Sprintf("Could not delete workflow '%s'.", in.Id), Id: in.Id}
	}

	return &ws.WorkflowResponse{Code: 0, Message: "Update OK", Id: in.Id}
}

// validateRequest returns error if request params are invalid
func (wh *workflowHandler) validateRequest(in *ws.WorkflowRequest) error {
	err := wh.validateId(in.Id)
	if err != nil {
		return err
	}

	err = wh.validateJson(in.Json)
	if err != nil {
		return err
	}

	return nil
}

// validateId returns error if id is invalid
func (wh *workflowHandler) validateId(id string) error {
	if id == "" {
		return fmt.Errorf("empty id given")
	}

	if bson.IsObjectIdHex(id) == false {
		return fmt.Errorf("invalid id given (is not ObjectId)")
	}

	return nil
}

// validateJson returns error if json is invalid
func (wh *workflowHandler) validateJson(json string) error {
	if json == "" {
		return fmt.Errorf("empty json given")
	}

	return nil
}


