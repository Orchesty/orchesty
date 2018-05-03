package generator

import (
	"testing"
	"io/ioutil"
	"github.com/stretchr/testify/assert"
	"clever-monitor/workflow/pkg/hydrator"
	ws "clever-monitor/workflow/pkg/workflowservice/clevermonitor/analytics/protos/workflow"
	"gopkg.in/mgo.v2/bson"
)

func TestWorkflowGenerator_Generate_SimpleConfig(t *testing.T) {
	config, err := hydrator.StringToEditorConfig(getEditorJson(t, "editor_1.json"))
	assert.Nil(t, err)
	generator := NewWorkflowGenerator()

	wfs, err := generator.Generate(config, 555, "guid")
	assert.Nil(t, err)

	// Only config for selected items should be generated (some are skipped)
	assert.Len(t, wfs, 3)

	checkSequence(t, wfs)
	checkCommonProperties(t, wfs)

	checkNotifyConfig(t, wfs[0])
	checkWaitConfig(t, wfs[1])
	checkEmailConfig(t, wfs[2])
}

// getValidJsonExample returns valid json example in string
func getEditorJson(t *testing.T, file string) string {
	b, err := ioutil.ReadFile("../../examples/" + file)
	assert.Nil(t, err)

	return string(b)
}

func checkSequence(t *testing.T, wfs []*ws.WorkflowConfig) {
	// TODO - after rewriting to goroutines the order may change
	assert.Equal(t, "1", wfs[0].EditorItemId)
	assert.Len(t, wfs[0].Steps, 1)
	assert.Equal(t, wfs[1].Id, wfs[0].Steps[0].NextFlow.Id)

	assert.Equal(t, "2", wfs[1].EditorItemId)
	assert.Len(t, wfs[1].Steps, 1)
	assert.Equal(t, wfs[2].Id, wfs[1].Steps[0].NextFlow.Id)

	assert.Equal(t, "3", wfs[2].EditorItemId)
	assert.Len(t, wfs[2].Steps, 1)
	assert.Nil(t, wfs[2].Steps[0].NextFlow)
}

func checkCommonProperties(t *testing.T, wfs []*ws.WorkflowConfig) {
	for _, wf := range wfs {
		assert.True(t, bson.IsObjectIdHex(wf.Id))
		assert.Equal(t, 555, int(wf.ClientId))
		assert.Equal(t, "guid", wf.ClientGuid)
		assert.Empty(t, wf.Filter.InLists)
		assert.Len(t, wf.Filter.InSegments, 2)
		assert.Equal(t, "some segment", wf.Filter.InSegments[0])
		assert.Equal(t, "another segment", wf.Filter.InSegments[1])

		if len(wf.Steps) > 0 {
			assert.Empty(t, wf.Steps[0].Conditions)
		}
	}
}

func checkNotifyConfig(t *testing.T, wf *ws.WorkflowConfig) {
	assert.Len(t, wf.Steps, 1)
	assert.Equal(t, true, wf.Steps[0].Channels.Notify.Email)
	assert.Equal(t, false, wf.Steps[0].Channels.Notify.Aim)
}

func checkWaitConfig(t *testing.T, wf *ws.WorkflowConfig) {
	assert.Len(t, wf.Steps, 1)
	assert.Equal(t, 3600, int(wf.Steps[0].Wait.Duration))
}

func checkEmailConfig(t *testing.T, wf *ws.WorkflowConfig) {
  	assert.Len(t, wf.Steps, 1)
	assert.Equal(t, "507f1f77bcfaecd7994390ce", wf.Steps[0].Channels.Email.TemplateId)
	assert.Equal(t, "Tomas Sedlacek", wf.Steps[0].Channels.Email.SenderName)
	assert.Equal(t, "sedlacek.t@hanaboso.com", wf.Steps[0].Channels.Email.SenderEmail)
	assert.Equal(t, "Some subject", wf.Steps[0].Channels.Email.Subject)
}
