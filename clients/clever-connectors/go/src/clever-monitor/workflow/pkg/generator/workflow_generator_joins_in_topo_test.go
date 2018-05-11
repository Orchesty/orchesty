package generator

import (
	"testing"
	"github.com/stretchr/testify/assert"
	"clever-monitor/workflow/pkg/hydrator"
	ws "clever-monitor/workflow/pkg/workflowservice/clevermonitor/analytics/protos/workflow"
)

// Generate and check workflow configs from given editor config containing condition node
func TestWorkflowGenerator_Generate_JoinsConfig(t *testing.T) {
	config, err := hydrator.StringToEditorConfig(getEditorJson(t, "editor_3.json"))
	assert.Nil(t, err)
	generator := NewWorkflowGenerator()

	wfs, err := generator.Generate(config, 555, "guid")
	assert.Nil(t, err)

	// Only config for selected items should be generated (some are skipped)
	assert.Len(t, wfs, 3)

	checkJoinsSequence(t, wfs)

	// save output to file in order to be check-able by human
	saveWorkflowConfigs(t, wfs, "workflow_3")
}

// check if the configs are ordered properly
func checkJoinsSequence(t *testing.T, wfs []*ws.WorkflowConfig) {
	// condition node
	assert.Equal(t, "idcondition", wfs[0].EditorItemId)
	assert.Len(t, wfs[0].Steps, 2)
	assert.Equal(t, wfs[1].Id, wfs[0].Steps[0].NextFlow.Id) // condition -> notify

	// notify node
	assert.Equal(t, "idnotify", wfs[1].EditorItemId)
	assert.Len(t, wfs[1].Steps, 1)
	assert.Equal(t, wfs[2].Id, wfs[1].Steps[0].NextFlow.Id) // notify -> email

	// email node
	assert.Equal(t, "idemail", wfs[2].EditorItemId)
	assert.Len(t, wfs[2].Steps, 1)
	assert.Nil(t, wfs[2].Steps[0].NextFlow) // email -> ()
}
