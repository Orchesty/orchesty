package hydrator

import (
	"github.com/golang/protobuf/jsonpb"
	ws "clever-monitor/workflow/pkg/workflowservice/clevermonitor/analytics/protos/workflow"
)

func WorkflowConfigToString(conf *ws.WorkflowConfig) (string, error) {
	marshaler := jsonpb.Marshaler{}
	str, err := marshaler.MarshalToString(conf)
	if err != nil {
		return "", err
	}

	return str, nil
}

func StringToWorkflowConfig(json string) (*ws.WorkflowConfig, error){
	var conf ws.WorkflowConfig
	err := jsonpb.UnmarshalString(json, &conf)

	return &conf, err
}

func StringToEditorConfig(json string) (*ws.EditorConfig, error){
	var conf ws.EditorConfig
	err := jsonpb.UnmarshalString(json, &conf)

	return &conf, err
}