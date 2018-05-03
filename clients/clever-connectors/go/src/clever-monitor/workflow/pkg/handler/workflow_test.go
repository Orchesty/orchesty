package handler

import (
	"testing"
	"github.com/stretchr/testify/assert"
	ws "clever-monitor/workflow/pkg/workflowservice/clevermonitor/analytics/protos/workflow"
	"fmt"
	"io/ioutil"
	"clever-monitor/workflow/pkg/storage"
	"gopkg.in/mgo.v2/bson"
)

const (
	shouldEndWithError = "aaaaaaaaaaaaaaaaaaaaaaaa"
	successObjectId    = "5aa228e1922688649d414d84"
	editorConfigFile   = "../../examples/editor_1.json"
)

type storageMock struct{}

func (s *storageMock) Create(editorConfig string, workflowConfigs []string) (string, error) {
	if editorConfig == shouldEndWithError {
		return "", fmt.Errorf("storage create error")
	}

	return successObjectId, nil
}

func (s *storageMock) Delete(editorConfigId string) (error) {
	if editorConfigId == shouldEndWithError {
		return fmt.Errorf("storage delete error")
	}

	return nil
}

func (s *storageMock) FindEditorConfig(id string) (*storage.EditorRecord, error) {
	if id == shouldEndWithError {
		return &storage.EditorRecord{}, fmt.Errorf("storage find error")
	}

	return &storage.EditorRecord{Json: "{\"foo\": \"bar\"}", Id: bson.NewObjectId().Hex()}, nil
}

func (s *storageMock) FindWorkflowConfig(id string) (*storage.WorkflowRecord, error) {
	if id == shouldEndWithError {
		return &storage.WorkflowRecord{}, fmt.Errorf("storage find error")
	}

	return &storage.WorkflowRecord{Json: "{\"foo\": \"bar\"}", Id: bson.NewObjectId().Hex()}, nil
}

func (s *storageMock) FindAllWorkflowConfigs(editorId string) ([]*storage.WorkflowRecord, error) {
	var records []*storage.WorkflowRecord
	if editorId == shouldEndWithError {
		return records, fmt.Errorf("storage find error")
	}

	records = append(
		records,
		&storage.WorkflowRecord{
			Json:     "{\"foo\": \"bar\"}",
			Id:       bson.NewObjectId().Hex(),
			EditorId: editorId,
		})

	return records, nil
}

type generatorMock struct{}

func (gen *generatorMock) Generate(
	editor *ws.EditorConfig,
	clientId int,
	clientGuid string,
) ([]*ws.WorkflowConfig, error) {
	var generated []*ws.WorkflowConfig

	return generated, nil
}

// TODO - add read tests

func TestWorkflowHandler_HandleCreate(t *testing.T) {
	handler := NewWorkflowHandler(&storageMock{}, &generatorMock{})

	response := handler.HandleCreate(&ws.CreateRequest{})
	assert.Equal(t, int32(InvalidRequest), response.Code)
	assert.Equal(t, messageErrorJsonEmpty, response.Message)

	response = handler.HandleCreate(&ws.CreateRequest{Json: "{\"foo\": \"invalid json\"}"})
	assert.Equal(t, int32(InvalidRequest), response.Code)
	assert.Equal(t, messageErrorJsonInvalid, response.Message)

	b, err := ioutil.ReadFile(editorConfigFile)
	assert.Nil(t, err)

	response = handler.HandleCreate(&ws.CreateRequest{Json: string(b)})
	assert.Equal(t, int32(OK), response.Code)
	assert.Equal(t, messageSuccessCreated, response.Message)
}

func TestWorkflowHandler_Handle_Delete(t *testing.T) {
	handler := NewWorkflowHandler(&storageMock{}, &generatorMock{})

	response := handler.HandleDelete(&ws.DeleteRequest{})
	assert.Equal(t, int32(InvalidRequest), response.Code)
	assert.Equal(t, messageErrorIdEmpty, response.Message)

	response = handler.HandleDelete(&ws.DeleteRequest{Id: "invalid-id"})
	assert.Equal(t, int32(InvalidRequest), response.Code)
	assert.Equal(t, messageErrorIdInvalid, response.Message)

	response = handler.HandleDelete(&ws.DeleteRequest{Id: shouldEndWithError})
	assert.Equal(t, int32(NotFound), response.Code)
	assert.Equal(t, "storage delete error", response.Message)

	response = handler.HandleDelete(&ws.DeleteRequest{Id: successObjectId})
	assert.Equal(t, int32(OK), response.Code)
	assert.Equal(t, messageSuccessDeleted, response.Message)
}
