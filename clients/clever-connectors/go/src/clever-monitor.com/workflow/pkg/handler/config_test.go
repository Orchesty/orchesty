package handler

import (
	"testing"
	ws "clever-monitor.com/workflow/workflowservice"
	"fmt"
	"github.com/stretchr/testify/assert"
	"io/ioutil"
)

func TestConfigHandler_ConfigToJson(t *testing.T) {
	conf := &ws.WorkflowConfig{}

	conf.IdConfig = "id"
	conf.Type = "type"
	conf.ClientGuild = "guid"
	conf.ClientId = 999
	conf.DomainId = "domain"

	conf.Filter = &ws.WorkflowConfig_Filter{}
	conf.Filter.Priority = 1
	conf.Filter.FilteringVariable = "var"
	conf.Filter.InSegment = []string{"seg1", "seg2"}
	conf.Filter.NotInSegment = []string{}
	conf.Filter.InTag = []string{"tag1"}

	//step := &ws.WorkflowConfig_Step{}
	//step.Recommendations.RecommendationType = ws.WorkflowConfig_Step_Recommendation_CUSTOM
	////step.Segmentation.SegmentationType = ws.WorkflowConfig_Step_Segmentation_BOTH
	//step.Channels.Email.Template = "template"
	//step.Channels.Email.DynamicFields = "dynamic_fields"
	//step.Channels.Email.SendTime = ws.WorkflowConfig_Step_ChannelMap_DELAYED
	//
	//steps := []*ws.WorkflowConfig_Step{step}
	//
	//conf.Steps = steps

	//conf.Steps = append(conf.Steps, step)

	handler := NewConfigHandler(&storageMock{})

	str, err := handler.configToJson(conf)
	assert.Nil(t, err)
	fmt.Println(str)
	//assert.Equal(t, "{\"domainId\":\"domain\",\"type\":\"type\",\"clientId\":999,\"clientGuild\":\"guid\",\"idConfig\":\"id\",\"filter\":{\"inSegment\":[\"seg1\",\"seg2\"],\"notInSegment\":[],\"inTag\":[\"tag1\"],\"priority\":1,\"filteringVariable\":\"var\"}}", str)

	regenerated, err := handler.jsonToConfig(str)
	assert.Nil(t, err)
	assert.EqualValues(t, conf, regenerated)
}

func TestConfigHandler_JsonToConfig(t *testing.T) {
	file := "example.json"
	assert.FileExists(t, file)

	b, err := ioutil.ReadFile("example.json")
	assert.Nil(t, err)
	assert.True(t, len(string(b)) > 0)

	handler := NewConfigHandler(&storageMock{})
	conf, err := handler.jsonToConfig(string(b))

	fmt.Println(conf)

	// Basic fields
	assert.Equal(t, "domainId_compatible", conf.DomainId)
	assert.Equal(t, "page_view_or_other", conf.Type)
	assert.Equal(t, int32(555), conf.ClientId)
	assert.Equal(t, "client_guid", conf.ClientGuild)
	assert.Equal(t, "hash_configu", conf.IdConfig)

	// Filter fields
	assert.Len(t, conf.Filter.InSegment, 1)
	assert.Equal(t, "hash_segmentu", conf.Filter.InSegment[0])
	assert.Len(t, conf.Filter.NotInSegment, 1)
	assert.Equal(t, "hash_segmentu", conf.Filter.NotInSegment[0])
	assert.Len(t, conf.Filter.InTag, 1)
	assert.Equal(t, "tag", conf.Filter.InTag[0])
	assert.Equal(t, int32(10), conf.Filter.Priority)
	assert.Equal(t, "variable_name/column_name/etc", conf.Filter.FilteringVariable)

	// Steps fields
	assert.Len(t, conf.Steps, 1)
}
