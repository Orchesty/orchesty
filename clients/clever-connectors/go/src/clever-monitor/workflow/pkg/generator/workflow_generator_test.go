package generator

import (
	"testing"
	"io/ioutil"
	"github.com/stretchr/testify/assert"
	"clever-monitor/workflow/pkg/hydrator"
	ws "clever-monitor/workflow/pkg/workflowservice"
	"gopkg.in/mgo.v2/bson"
)

func TestWorkflowGenerator_Generate(t *testing.T) {
	config, err := hydrator.StringToEditorConfig(getEditorJson(t, "editor.json"))
	assert.Nil(t, err)
	generator := NewWorkflowGenerator()

	wfs, err := generator.Generate(config, 555, "guid")
	assert.Nil(t, err)
	assert.Len(t, wfs, 3)

	checkContinuity(t, wfs)
	checkGeneratedRoot(t, wfs[0])
	checkGeneratedFirstNode(t, wfs[1])
	checkGeneratedSecondNode(t, wfs[2])
}

// getValidJsonExample returns valid json example in string
func getEditorJson(t *testing.T, file string) string {
	b, err := ioutil.ReadFile("../../examples/" + file)
	assert.Nil(t, err)

	return string(b)
}

func checkContinuity(t *testing.T,wfs []*ws.WorkflowConfig) {
	// TODO - after rewriting to goroutines the order may change
	id1 := wfs[1].Id
	id2 := wfs[2].Id

	assert.Equal(t, id1, wfs[0].Steps[0].NextFlow.Id)
	assert.Equal(t, id2, wfs[1].Steps[0].NextFlow.Id)
	assert.Nil(t, wfs[2].Steps)
}

func checkGeneratedRoot(t *testing.T, wf *ws.WorkflowConfig) {
	assert.True(t, bson.IsObjectIdHex(wf.Id))
	assert.Equal(t, 555, int(wf.ClientId))
	assert.Equal(t, "guid", wf.ClientGuid)
	assert.Equal(t, []string{}, wf.Filter.InSegment)
	assert.Equal(t, []string{}, wf.Filter.NotInSegment)
	assert.Nil(t, wf.Filter.FilteringVariable)
	assert.Len(t, wf.Steps, 1)
	assert.Equal(t, "true", wf.Steps[0].Condition)
	// TODO - check settings
}

func checkGeneratedFirstNode(t *testing.T, wf *ws.WorkflowConfig) {
	assert.True(t, bson.IsObjectIdHex(wf.Id))
	assert.Equal(t, 555, int(wf.ClientId))
	assert.Equal(t, "guid", wf.ClientGuid)
	assert.Len(t, wf.Steps, 1)
	assert.Equal(t, "true", wf.Steps[0].Condition)
	// TODO - check settings
}

func checkGeneratedSecondNode(t *testing.T, wf *ws.WorkflowConfig) {
	assert.True(t, bson.IsObjectIdHex(wf.Id))
	assert.Equal(t, 555, int(wf.ClientId))
	assert.Equal(t, "guid", wf.ClientGuid)
	assert.Len(t, wf.Steps, 0)
	// TODO - check settings
}
