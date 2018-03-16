package handler

import (
	"clever-monitor/workflow/pkg/storage"
	"github.com/golang/protobuf/jsonpb"
	ws "clever-monitor/workflow/pkg/workflowservice"
	"strings"
	"fmt"
)

type ConfigProvider interface {
	GetConfig(in *ws.WorkflowRequest) *ws.WorkflowConfig
}

type configHandler struct {
	storage storage.Finder
}

func NewConfigHandler(storage storage.Finder) *configHandler {
	return &configHandler{storage: storage}
}

func (ch *configHandler) GetConfig(in *ws.WorkflowRequest) *ws.WorkflowConfig {
	// todo validate id value
	json, err := ch.storage.Find(in.Id)
	if err != nil {
		// todo what to return if not found by id?
		return &ws.WorkflowConfig{}
	}

	config := &ws.WorkflowConfig{}
	err = jsonpb.Unmarshal(strings.NewReader(json), config)
	if err != nil {
		fmt.Println(err.Error())
	}

	return config
}

func configToJson(conf *ws.WorkflowConfig) (string, error) {
	marshaler := jsonpb.Marshaler{}
	str, err := marshaler.MarshalToString(conf)
	if err != nil {
		return "", err
	}

	return str, nil
}

func jsonToConfig(json string) (*ws.WorkflowConfig, error){
	var conf ws.WorkflowConfig
	err := jsonpb.UnmarshalString(json, &conf)

	return &conf, err
}
