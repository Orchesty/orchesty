package handler

import (
	"fmt"
	"clever-monitor/workflow/pkg/storage"
	ws "clever-monitor/workflow/pkg/workflowservice"
	"gopkg.in/mgo.v2/bson"
)

const (
	messageErrorMethod = "Invalid method name '%s'"
	messageErrorIdEmpty = "empty id given"
	messageErrorIdInvalid = "invalid id given (is not ObjectId)"
	messageErrorJsonEmpty = "empty json given"
	messageErrorJsonInvalid = "invalid json given"

	messageSuccessCreated = "created"
	messageSuccessUpdated= "updated"
	messageSuccessFound = "found"
	messageSuccessDeleted = "deleted"
)

type workflowHandler struct {
	storage storage.Storage
}

func NewWorkflowHandler(storage storage.Storage) *workflowHandler {
	return &workflowHandler{storage: storage}
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
		return &ws.WorkflowResponse{Code: int32(InvalidRequest), Message: fmt.Sprintf(messageErrorMethod, method)}
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

	return &ws.WorkflowResponse{Code: int32(OK), Message: messageSuccessCreated, Id: id}
}

// handleRead tries to find and return the content of record by it's id
func (wh *workflowHandler) handleRead(in *ws.WorkflowRequest) *ws.WorkflowResponse {
	err := wh.validateId(in.Id)
	if err != nil {
		return &ws.WorkflowResponse{Code: int32(InvalidRequest), Message: err.Error()}
	}

	data, err := wh.storage.Find(in.Id)
	if err != nil {
		return &ws.WorkflowResponse{Code: int32(NotFound), Message: err.Error()}
	}

	return &ws.WorkflowResponse{Code: int32(OK), Message: messageSuccessFound, Id: in.Id, Json: data}
}

// handleUpdate set's new content to storage for given id
func (wh *workflowHandler) handleUpdate(in *ws.WorkflowRequest) *ws.WorkflowResponse {
	err := wh.validateRequest(in)
	if err != nil {
		return &ws.WorkflowResponse{Code: int32(InvalidRequest), Message: err.Error()}
	}

	_, err = wh.storage.Update(in.Id, in.Json)
	if err != nil {
		return &ws.WorkflowResponse{Code: int32(InternalError), Message: err.Error(), Id: in.Id}
	}

	return &ws.WorkflowResponse{Code: int32(OK), Message: messageSuccessUpdated, Id: in.Id}
}

// handleDelete removes the record from storage by it's id
func (wh *workflowHandler) handleDelete(in *ws.WorkflowRequest) *ws.WorkflowResponse {
	err := wh.validateId(in.Id)
	if err != nil {
		return &ws.WorkflowResponse{Code: int32(InvalidRequest), Message: err.Error()}
	}

	err = wh.storage.Delete(in.Id)
	if err != nil {
		return &ws.WorkflowResponse{Code: int32(NotFound), Message: err.Error(), Id: in.Id}
	}

	return &ws.WorkflowResponse{Code: int32(OK), Message: messageSuccessDeleted, Id: in.Id}
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
		return fmt.Errorf(messageErrorIdEmpty)
	}

	if bson.IsObjectIdHex(id) == false {
		return fmt.Errorf(messageErrorIdInvalid)
	}

	return nil
}

// validateJson returns error if json is invalid
func (wh *workflowHandler) validateJson(json string) error {
	if json == "" {
		return fmt.Errorf(messageErrorJsonEmpty)
	}

	_, err := jsonToConfig(json)
	if err != nil {
		return fmt.Errorf(messageErrorJsonInvalid)
	}

	return nil
}


