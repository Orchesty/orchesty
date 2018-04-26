package generator

import (
	"testing"
	"io/ioutil"
	"github.com/stretchr/testify/assert"
	"clever-monitor/workflow/pkg/hydrator"
	ws "clever-monitor/workflow/pkg/workflowservice"
	"gopkg.in/mgo.v2/bson"
)

func TestWorkflowGenerator_Generate_SimpleConfig(t *testing.T) {
	config, err := hydrator.StringToEditorConfig(getEditorJson(t, "editor.json"))
	assert.Nil(t, err)
	generator := NewWorkflowGenerator()

	wfs, err := generator.Generate(config, 555, "guid")
	assert.Nil(t, err)
	assert.Len(t, wfs, 3)

	checkWorkflowOrder(t, wfs)
	checkGeneratedWorkflowsProperties(t, wfs)
}

// getValidJsonExample returns valid json example in string
func getEditorJson(t *testing.T, file string) string {
	b, err := ioutil.ReadFile("../../examples/" + file)
	assert.Nil(t, err)

	return string(b)
}

func checkWorkflowOrder(t *testing.T, wfs []*ws.WorkflowConfig) {
	// TODO - after rewriting to goroutines the order may change
	assert.Equal(t, "1", wfs[0].EditorItemId)
	assert.Len(t, wfs[0].Steps, 1)
	assert.Equal(t, wfs[1].Id, wfs[0].Steps[0].NextFlow.Id)

	assert.Equal(t, "2", wfs[1].EditorItemId)
	assert.Len(t, wfs[1].Steps, 1)
	assert.Equal(t, wfs[2].Id, wfs[1].Steps[0].NextFlow.Id)

	assert.Equal(t, "3", wfs[2].EditorItemId)
	assert.Len(t, wfs[2].Steps, 0)
}

func checkGeneratedWorkflowsProperties(t *testing.T, wfs []*ws.WorkflowConfig) {
	for _, wf := range wfs {
		assert.True(t, bson.IsObjectIdHex(wf.Id))
		assert.Equal(t, 555, int(wf.ClientId))
		assert.Equal(t, "guid", wf.ClientGuid)

		if len(wf.Steps) > 0 {
			assert.Equal(t, "true", wf.Steps[0].Condition)
		}
	}
}
