package handler

import (
	"clever-monitor.com/workflow/pkg/storage"
	"github.com/golang/protobuf/jsonpb"
	ws "clever-monitor.com/workflow/workflowservice"
	"strings"
)

type ConfigProvider interface {
	GetConfig(in *ws.WorkflowRequest) *ws.WorkflowConfig
}

type configHandler struct {
	storage storage.Finder
}

func NewConfigHandler(storage storage.Storage) *configHandler {
	return &configHandler{storage: storage}
}

func (ch *configHandler) GetConfig(in *ws.WorkflowRequest) *ws.WorkflowConfig {
	// todo valdiate id value
	json, err := ch.storage.Find(in.Id)
	if err != nil {
		// todo what to return if not found by id?
		return &ws.WorkflowConfig{}
	}

	config := &ws.WorkflowConfig{}
	jsonpb.Unmarshal(strings.NewReader(json), config)

	return config
}
