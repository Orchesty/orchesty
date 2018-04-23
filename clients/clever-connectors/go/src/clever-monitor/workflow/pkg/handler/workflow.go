package handler

import (
	"fmt"
	"clever-monitor/workflow/pkg/storage"
	"clever-monitor/workflow/pkg/generator"
	ws "clever-monitor/workflow/pkg/workflowservice"
	"gopkg.in/mgo.v2/bson"
)

const (
	messageErrorMethod      = "Invalid method name '%s'"
	messageErrorIdEmpty     = "empty id given"
	messageErrorIdInvalid   = "invalid id given (is not ObjectId)"
	messageErrorJsonEmpty   = "empty json given"
	messageErrorJsonInvalid = "invalid json given"

	messageSuccessCreated = "created"
	messageSuccessUpdated = "updated"
	messageSuccessFound   = "found"
	messageSuccessDeleted = "deleted"
)

type workflowHandler struct {
	storage   storage.Storage
	generator generator.Generator
}

// NewWorkflowHandler creates and returns new workflowHandler struct
func NewWorkflowHandler(storage storage.Storage, generator generator.Generator) *workflowHandler {
	return &workflowHandler{
		storage:   storage,
		generator: generator,
	}
}

// handleCreate validates and saves given editor json
// then it generates and saves eventManager json from editor json
func (wh *workflowHandler) HandleCreate(in *ws.CreateRequest) *ws.WorkflowResponse {
	editorConf, err := wh.validateEditorJson(in.Json)
	if err != nil {
		return &ws.WorkflowResponse{Code: int32(InvalidRequest), Message: err.Error()}
	}

	workflowConfigs, err := wh.generator.GenerateStrings(editorConf)
	if err != nil {
		return &ws.WorkflowResponse{Code: int32(InternalError), Message: err.Error()}
	}

	id, err := wh.storage.Create(in.Json, workflowConfigs)
	if err != nil {
		return &ws.WorkflowResponse{Code: int32(InternalError), Message: err.Error()}
	}

	return &ws.WorkflowResponse{Code: int32(OK), Message: messageSuccessCreated, Id: id}
}

// TODO - refactor - reuse code from HandleCreate and HandleDelete
// handleUpdate set's new content to storage for given id
func (wh *workflowHandler) HandleUpdate(in *ws.UpdateRequest) *ws.WorkflowResponse {
	_, editorConf, err := wh.validateIdAndJson(in.Id, in.Json)
	if err != nil {
		return &ws.WorkflowResponse{Code: int32(InvalidRequest), Message: err.Error()}
	}

	err = wh.storage.Delete(in.Id)
	if err != nil {
		return &ws.WorkflowResponse{Code: int32(InternalError), Message: err.Error(), Id: in.Id}
	}

	workflowConfigs, err := wh.generator.GenerateStrings(editorConf)
	if err != nil {
		return &ws.WorkflowResponse{Code: int32(InternalError), Message: err.Error()}
	}

	id, err := wh.storage.Create(in.Json, workflowConfigs)
	if err != nil {
		return &ws.WorkflowResponse{Code: int32(InternalError), Message: err.Error()}
	}

	return &ws.WorkflowResponse{Code: int32(OK), Message: messageSuccessUpdated, Id: id}
}

// handleDelete removes the record from storage by it's id
func (wh *workflowHandler) HandleDelete(in *ws.DeleteRequest) *ws.WorkflowResponse {
	id, err := wh.validateId(in.Id)
	if err != nil {
		return &ws.WorkflowResponse{Code: int32(InvalidRequest), Message: err.Error()}
	}

	err = wh.storage.Delete(id)
	if err != nil {
		return &ws.WorkflowResponse{Code: int32(NotFound), Message: err.Error(), Id: id}
	}

	return &ws.WorkflowResponse{Code: int32(OK), Message: messageSuccessDeleted, Id: id}
}

// handleReadEditor tries to find and return the content of record by it's id
func (wh *workflowHandler) HandleReadEditorConfig(in *ws.ReadRequest) *ws.WorkflowResponse {
	id, err := wh.validateId(in.Id)
	if err != nil {
		return &ws.WorkflowResponse{Code: int32(InvalidRequest), Message: err.Error()}
	}

	data, err := wh.storage.FindEditorConfig(id)
	if err != nil {
		return &ws.WorkflowResponse{Code: int32(NotFound), Message: err.Error()}
	}

	return &ws.WorkflowResponse{Code: int32(OK), Message: messageSuccessFound, Id: id, Json: data}
}

// handleReadEditor tries to find and return the content of record by it's id
func (wh *workflowHandler) HandleReadWorkflowConfig(in *ws.ReadRequest) *ws.WorkflowResponse {
	id, err := wh.validateId(in.Id)
	if err != nil {
		return &ws.WorkflowResponse{Code: int32(InvalidRequest), Message: err.Error()}
	}

	data, err := wh.storage.FindWorkflowConfig(id)
	if err != nil {
		return &ws.WorkflowResponse{Code: int32(NotFound), Message: err.Error()}
	}

	return &ws.WorkflowResponse{Code: int32(OK), Message: messageSuccessFound, Id: id, Json: data}
}

// validateRequest returns error if request params are invalid
func (wh *workflowHandler) validateIdAndJson(id string, json string) (string, *ws.EditorConfig, error) {
	_, err := wh.validateId(id)
	if err != nil {
		return id, nil, err
	}

	conf, err := wh.validateEditorJson(json)
	if err != nil {
		return id, nil, err
	}

	return id, conf, nil
}

// validateId returns error if id is invalid
func (wh *workflowHandler) validateId(id string) (string, error) {
	if id == "" {
		return id, fmt.Errorf(messageErrorIdEmpty)
	}

	if bson.IsObjectIdHex(id) == false {
		return id, fmt.Errorf(messageErrorIdInvalid)
	}

	return id, nil
}

// validateJson returns error if json is invalid
func (wh *workflowHandler) validateEditorJson(json string) (*ws.EditorConfig, error) {
	if json == "" {
		return nil, fmt.Errorf(messageErrorJsonEmpty)
	}

	e, err := stringToEditorConfig(json)
	if err != nil {
		return nil, fmt.Errorf(messageErrorJsonInvalid)
	}

	return e, nil
}
