package generator

import (
	"testing"
	"io/ioutil"
	"github.com/stretchr/testify/assert"
	"clever-monitor/workflow/pkg/hydrator"
	ws "clever-monitor/workflow/pkg/workflowservice/clevermonitor/analytics/protos/workflow"
	"gopkg.in/mgo.v2/bson"
	"fmt"
)

const examplesPath = "../../examples/"
const generatedPath = "../../examples/generated/"

// Generate and check workflow configs from given editor config containing simple linear topology
func TestWorkflowGenerator_Generate_Editor1(t *testing.T) {
	config, err := hydrator.StringToEditorConfig(getEditorJson(t, "editor_1.json"))
	assert.Nil(t, err)
	generator := NewRecursiveGenerator()

	wfs, err := generator.Generate(config, 555, "guid")
	assert.Nil(t, err)

	// Only config for selected items should be generated (some are skipped)
	assert.Len(t, wfs, 4)

	checkEditor1Sequence(t, wfs)
	checkEditor1CommonProperties(t, wfs)

	checkEditor1NotifyConfig(t, wfs[0])
	checkEditor1WaitConfig(t, wfs[1])
	checkEditor1EmailConfig(t, wfs[2])
	checkEditor1DistributeConfig(t, wfs[3])

	// save output to file in order to be check-able by human
	saveWorkflowConfigs(t, wfs, "workflow_1")
}

// check if the configs are ordered properly
func checkEditor1Sequence(t *testing.T, wfs []*ws.WorkflowConfig) {
	assert.Equal(t, "1", wfs[0].EditorItemId)
	assert.Len(t, wfs[0].Steps, 1)
	assert.Equal(t, wfs[1].Id, wfs[0].Steps[0].NextFlow.Id)

	assert.Equal(t, "2", wfs[1].EditorItemId)
	assert.Len(t, wfs[1].Steps, 1)
	assert.Equal(t, wfs[2].Id, wfs[1].Steps[0].NextFlow.Id)

	assert.Equal(t, "3", wfs[2].EditorItemId)
	assert.Len(t, wfs[2].Steps, 1)
	assert.Equal(t, wfs[3].Id, wfs[2].Steps[0].NextFlow.Id)

	assert.Equal(t, "4", wfs[3].EditorItemId)
	assert.Len(t, wfs[3].Steps, 1)
	assert.Nil(t, wfs[3].Steps[0].NextFlow)
}

// check properties that should have all nodes have in common
func checkEditor1CommonProperties(t *testing.T, wfs []*ws.WorkflowConfig) {
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

// check generated notify workflow config
func checkEditor1NotifyConfig(t *testing.T, wf *ws.WorkflowConfig) {
	assert.Len(t, wf.Steps, 1)
	assert.Equal(t, true, wf.Steps[0].Channels.Notify.Email)
	assert.Equal(t, false, wf.Steps[0].Channels.Notify.Aim)
}

// check generated wait workflow config
func checkEditor1WaitConfig(t *testing.T, wf *ws.WorkflowConfig) {
	assert.Len(t, wf.Steps, 1)
	assert.Equal(t, 3600, int(wf.Steps[0].Wait.Duration))
}

// check generated email workflow config
func checkEditor1EmailConfig(t *testing.T, wf *ws.WorkflowConfig) {
  	assert.Len(t, wf.Steps, 1)
	assert.Equal(t, "507f1f77bcfaecd7994390ce", wf.Steps[0].Channels.Email.TemplateId)
	assert.Equal(t, "Tomas Sedlacek", wf.Steps[0].Channels.Email.SenderName)
	assert.Equal(t, "sedlacek.t@hanaboso.com", wf.Steps[0].Channels.Email.SenderEmail)
	assert.Equal(t, "Some subject", wf.Steps[0].Channels.Email.Subject)
}

// check generated distribute workflow config
func checkEditor1DistributeConfig(t *testing.T, wf *ws.WorkflowConfig) {
	assert.Len(t, wf.Steps, 1)
	assert.Equal(t, ws.DistributeType_ADD, wf.Steps[0].Channels.Actions[0].ActionType)
	assert.Equal(t, "list identification", wf.Steps[0].Channels.Actions[0].ActionSubject)
}

// getValidJsonExample returns valid json example in string
func getEditorJson(t *testing.T, file string) string {
	b, err := ioutil.ReadFile(examplesPath + file)
	assert.Nil(t, err)

	return string(b)
}

// saveWorkflowConfigs saves generated config json string to files for better visual check
func saveWorkflowConfigs(t *testing.T, wfs []*ws.WorkflowConfig, file string) {
	for key, wf := range wfs {
		str, err := hydrator.WorkflowConfigToString(wf)
		assert.Nil(t, err)

		path := fmt.Sprintf("%s%s_%d.json", generatedPath, file, key)
		err = ioutil.WriteFile(path, []byte(str), 0644)
		assert.Nil(t, err)
	}
}
