package handler

import (
	"fmt"
	"clever-monitor/workflow/pkg/storage"
	"clever-monitor/workflow/pkg/generator"
	ws "clever-monitor/workflow/pkg/workflowservice"
	"gopkg.in/mgo.v2/bson"
	"clever-monitor/workflow/pkg/hydrator"
)

const (
	messageErrorIdEmpty     = "empty id given"
	messageErrorIdInvalid   = "invalid id given (is not ObjectId)"
	messageErrorJsonEmpty   = "empty json given"
	messageErrorJsonInvalid = "invalid json given"

	messageSuccessCreated = "created"
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
	editorConf, err := wh.validateEditorConfigJson(in.Json)
	if err != nil {
		return &ws.WorkflowResponse{Code: int32(InvalidRequest), Message: err.Error()}
	}

	workflowConfigs, err := wh.generator.Generate(editorConf, int(in.ClientId), in.ClientGuid)
	if err != nil {
		return &ws.WorkflowResponse{Code: int32(InternalError), Message: err.Error()}
	}

	workflowStrings, err := convertWorkflowConfigsToStrings(workflowConfigs)
	if err != nil {
		return &ws.WorkflowResponse{Code: int32(InternalError), Message: err.Error()}
	}

	id, err := wh.storage.Create(in.Json, workflowStrings)
	if err != nil {
		return &ws.WorkflowResponse{Code: int32(InternalError), Message: err.Error()}
	}

	return &ws.WorkflowResponse{Code: int32(OK), Message: messageSuccessCreated, Id: id}
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

	doc, err := wh.storage.FindEditorConfig(id)
	if err != nil {
		return &ws.WorkflowResponse{Code: int32(NotFound), Message: err.Error()}
	}

	return &ws.WorkflowResponse{Code: int32(OK), Message: messageSuccessFound, Id: id, Json: doc.Json}
}

// handleReadEditor tries to find and return the content of record by it's id
func (wh *workflowHandler) HandleReadWorkflowConfig(in *ws.ReadRequest) *ws.WorkflowResponse {
	id, err := wh.validateId(in.Id)
	if err != nil {
		return &ws.WorkflowResponse{Code: int32(InvalidRequest), Message: err.Error()}
	}

	doc, err := wh.storage.FindWorkflowConfig(id)
	if err != nil {
		return &ws.WorkflowResponse{Code: int32(NotFound), Message: err.Error()}
	}

	return &ws.WorkflowResponse{Code: int32(OK), Message: messageSuccessFound, Id: id, Json: doc.Json}
}

// handleReadEditor tries to find and return the content of record by it's id
func (wh *workflowHandler) HandleReadAllWorkflowConfigs(in *ws.ReadAllRequest) *ws.WorkflowResponse {
	id, err := wh.validateId(in.EditorId)
	if err != nil {
		return &ws.WorkflowResponse{Code: int32(InvalidRequest), Message: err.Error()}
	}

	_, err = wh.storage.FindAllWorkflowConfigs(id)
	if err != nil {
		return &ws.WorkflowResponse{Code: int32(NotFound), Message: err.Error()}
	}

	// TODO - fill Json with data from storage
	return &ws.WorkflowResponse{Code: int32(OK), Message: messageSuccessFound, Id: id, Json: ""}
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
func (wh *workflowHandler) validateEditorConfigJson(json string) (*ws.EditorConfig, error) {
	if json == "" {
		return nil, fmt.Errorf(messageErrorJsonEmpty)
	}

	e, err := hydrator.StringToEditorConfig(json)
	if err != nil {
		return nil, fmt.Errorf(messageErrorJsonInvalid)
	}

	return e, nil
}

func convertWorkflowConfigsToStrings(workflowConfigs []*ws.WorkflowConfig) ([]string, error) {
	var confStrings []string
	for _, wfConf := range workflowConfigs {
		str, err := hydrator.WorkflowConfigToString(wfConf)

		if err != nil {
			return confStrings, fmt.Errorf(
				"cannot convert workflow config to string '%s'. Error: %s", wfConf.Id, err.Error(),
			)
		}

		confStrings = append(confStrings, str)
	}

	return confStrings, nil
}
