package handler

import (
	"github.com/golang/protobuf/jsonpb"
	ws "clever-monitor/workflow/pkg/workflowservice"
)

func workflowConfigToString(conf *ws.WorkflowConfig) (string, error) {
	marshaler := jsonpb.Marshaler{}
	str, err := marshaler.MarshalToString(conf)
	if err != nil {
		return "", err
	}

	return str, nil
}

func stringToWorkflowConfig(json string) (*ws.WorkflowConfig, error){
	var conf ws.WorkflowConfig
	err := jsonpb.UnmarshalString(json, &conf)

	return &conf, err
}

func stringToEditorConfig(json string) (*ws.EditorConfig, error){
	var conf ws.EditorConfig
	err := jsonpb.UnmarshalString(json, &conf)

	return &conf, err
}