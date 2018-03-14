package handler

import (
	"testing"
	ws "clever-monitor.com/workflow/workflowservice"
	"github.com/stretchr/testify/assert"
	"io/ioutil"
)

func TestConfigHandler_JsonToConfig(t *testing.T) {
	handler := NewConfigHandler(&storageMock{})
	json := getExampleFileJson(t)

	conf, err := handler.jsonToConfig(json)

	assert.Nil(t, err)
	assertExampleConfig(t, conf)
}

func TestConfigHandler_ConfigToJson(t *testing.T) {
	handler := NewConfigHandler(&storageMock{})
	config := createConfig()

	str, err := handler.configToJson(config)
	assert.Nil(t, err)

	// check if we generate still same json
	b, err := ioutil.ReadFile("./examples/generated.json")
	assert.Equal(t, string(b), str)

	// by hydrating from json we should get config struct with same values as original
	regenerated, err := handler.jsonToConfig(str)
	assert.Nil(t, err)
	assert.EqualValues(t, config, regenerated)
}

func getExampleFileJson(t *testing.T) string {
	file := "./examples/example.json"
	assert.FileExists(t, file)

	b, err := ioutil.ReadFile(file)
	assert.Nil(t, err)
	assert.True(t, len(string(b)) > 0)

	return string(b)
}

func assertExampleConfig(t *testing.T, conf *ws.WorkflowConfig) {
	// Basic fields
	assert.Equal(t, "domainId_compatible", conf.DomainId)
	assert.Equal(t, "page_view_or_other", conf.Type)
	assert.Equal(t, int32(555), conf.ClientId)
	assert.Equal(t, "client_guid", conf.ClientGuid)
	assert.Equal(t, "hash_configu", conf.IdConfig)

	// Filter fields
	assert.Len(t, conf.Filter.InSegment, 1)
	assert.Equal(t, "hash_segmentu", conf.Filter.InSegment[0])
	assert.Len(t, conf.Filter.NotInSegment, 1)
	assert.Equal(t, "hash_segmentu", conf.Filter.NotInSegment[0])
	assert.Len(t, conf.Filter.InTag, 1)
	assert.Equal(t, "tag", conf.Filter.InTag[0])
	assert.Len(t, conf.Filter.NotInTag, 1)
	assert.Equal(t, "tag", conf.Filter.NotInTag[0])
	assert.Equal(t, int32(10), conf.Filter.Priority)
	assert.Equal(t, "variable_name/column_name/etc", conf.Filter.FilteringVariable)

	// Step fields
	assert.Len(t, conf.Steps, 1)
	step := conf.Steps[0]

	assert.Equal(t, "10<x<30", step.Condition)
	assert.Equal(t, ws.WorkflowConfig_Step_Recommendation_CUSTOM, step.Recommendations.RecommendationType)
	assert.Equal(t, ws.WorkflowConfig_Step_Segmentation_BOTH, step.Segmentation.SegmentationType)

	assert.Equal(t, "template_id", step.Channels.Email.Template)
	assert.Equal(t, "dynamic_field", step.Channels.Email.DynamicFields)
	assert.Equal(t, ws.WorkflowConfig_Step_ChannelMap_NOW, step.Channels.Email.SendTime)

	assert.Len(t, step.Channels.Action, 1)
	action := step.Channels.Action[0]
	assert.Equal(t, ws.WorkflowConfig_Step_ChannelMap_Action_LIST, action.ActionFamily)
	assert.Equal(t, ws.WorkflowConfig_Step_ChannelMap_Action_ADD, action.ActionType)
	assert.Equal(t, ws.WorkflowConfig_Step_ChannelMap_Action_TRIGGER, action.ActionTime)
	assert.Equal(t, ws.WorkflowConfig_Step_ChannelMap_Action_EMPTY, action.ActionTrigger)
	assert.Equal(t, "subject", action.ActionSubject)

	assert.Equal(t, int32(666), step.NextFlow.ClientId)
	assert.Equal(t, "synthetic", step.NextFlow.Type)
	assert.Equal(t, "config_hash", step.NextFlow.Config)
}

func createConfig() *ws.WorkflowConfig {
	conf := &ws.WorkflowConfig{}

	conf.IdConfig = "id"
	conf.Type = "type"
	conf.ClientGuid = "guid"
	conf.ClientId = 999
	conf.DomainId = "domain"

	conf.Filter = &ws.WorkflowConfig_Filter{}
	conf.Filter.Priority = 1
	conf.Filter.FilteringVariable = "var"
	conf.Filter.InSegment = []string{"seg1", "seg2"}
	conf.Filter.NotInSegment = []string{}
	conf.Filter.InTag = []string{"tag1"}
	conf.Filter.NotInTag = []string{"tag2"}

	stepOne := &ws.WorkflowConfig_Step{
		Condition: "x>0",
		Recommendations: &ws.WorkflowConfig_Step_Recommendation{
			RecommendationType: ws.WorkflowConfig_Step_Recommendation_CUSTOM,
		},
		Segmentation: &ws.WorkflowConfig_Step_Segmentation{
			SegmentationType: ws.WorkflowConfig_Step_Segmentation_BUSINESS,
		},
		Channels: &ws.WorkflowConfig_Step_ChannelMap{
			Email: &ws.WorkflowConfig_Step_ChannelMap_Email{
				Template:      "email_template",
				DynamicFields: "dyn1, dyn2",
				SendTime:      ws.WorkflowConfig_Step_ChannelMap_DELAYED,
			},
			Action: []*ws.WorkflowConfig_Step_ChannelMap_Action{
				{
					ActionSubject: "subject",
					ActionFamily:  ws.WorkflowConfig_Step_ChannelMap_Action_LIST,
					ActionType:    ws.WorkflowConfig_Step_ChannelMap_Action_UPDATE,
					ActionTime:    ws.WorkflowConfig_Step_ChannelMap_Action_TRIGGER,
					ActionTrigger: ws.WorkflowConfig_Step_ChannelMap_Action_CONDITION,
				},
				{
					ActionSubject: "another subject",
					ActionFamily:  ws.WorkflowConfig_Step_ChannelMap_Action_TAG,
					ActionType:    ws.WorkflowConfig_Step_ChannelMap_Action_ADD,
					ActionTime:    ws.WorkflowConfig_Step_ChannelMap_Action_NOW,
					ActionTrigger: ws.WorkflowConfig_Step_ChannelMap_Action_EMPTY,
				},
			},
		},
		NextFlow: &ws.WorkflowConfig_Step_NextFlow{
			ClientId: 999,
			Type:     "synthetic",
			Config:   "config_id",
		},
	}

	stepTwo := &ws.WorkflowConfig_Step{
		Condition: "x>0",
		Recommendations: &ws.WorkflowConfig_Step_Recommendation{
			RecommendationType: ws.WorkflowConfig_Step_Recommendation_CUSTOM,
		},
		Segmentation: &ws.WorkflowConfig_Step_Segmentation{
			SegmentationType: ws.WorkflowConfig_Step_Segmentation_BOTH,
		},
		Channels: &ws.WorkflowConfig_Step_ChannelMap{
			Email: &ws.WorkflowConfig_Step_ChannelMap_Email{
				Template:      "email_template",
				DynamicFields: "dyn1, dyn2",
				SendTime:      ws.WorkflowConfig_Step_ChannelMap_NOW,
			},
			Action: []*ws.WorkflowConfig_Step_ChannelMap_Action{
				{
					ActionSubject: "just subject",
				},
			},
		},
		NextFlow: &ws.WorkflowConfig_Step_NextFlow{
			ClientId: 999,
			Type:     "natural",
			Config:   "config_id",
		},
	}

	conf.Steps = append(conf.Steps, stepOne)
	conf.Steps = append(conf.Steps, stepTwo)

	return conf
}
