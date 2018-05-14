package generator

import (
	"testing"
	"github.com/stretchr/testify/assert"
	"clever-monitor/workflow/pkg/hydrator"
	ws "clever-monitor/workflow/pkg/workflowservice/clevermonitor/analytics/protos/workflow"
)

// Generate and check workflow configs from given editor config containing condition node
func TestWorkflowGenerator_Generate_Editor3(t *testing.T) {
	config, err := hydrator.StringToEditorConfig(getEditorJson(t, "editor_3.json"))
	assert.Nil(t, err)
	generator := NewRecursiveGenerator()

	wfs, err := generator.Generate(config, 555, "guid")
	assert.Nil(t, err)

	// Only config for selected items should be generated (some are skipped)
	assert.Len(t, wfs, 3)

	checkEditor3Sequence(t, wfs)

	// save output to file in order to be check-able by human
	saveWorkflowConfigs(t, wfs, "workflow_3")
}

// check if the configs are ordered properly
func checkEditor3Sequence(t *testing.T, wfs []*ws.WorkflowConfig) {
	//        CONDITION
	//       /         \
	//    NOTIFY   ->   EMAIL

	// condition node
	assert.Equal(t, "idcondition", wfs[0].EditorItemId)
	assert.Len(t, wfs[0].Steps, 2)
	stepToNotify := wfs[0].Steps[0]
	assert.Equal(t,"idconditionyes", stepToNotify.StepId)
	assert.Equal(t, wfs[1].Id, stepToNotify.NextFlow.Id) // condition -> notify
	stepToEmail := wfs[0].Steps[1]
	assert.Equal(t,"idconditionno", stepToEmail.StepId)
	assert.Equal(t, wfs[2].Id, stepToEmail.NextFlow.Id) // condition -> email

	// notify node
	assert.Equal(t, "idnotify", wfs[1].EditorItemId)
	assert.Len(t, wfs[1].Steps, 1)
	assert.Equal(t, wfs[2].Id, wfs[1].Steps[0].NextFlow.Id) // notify -> email

	// email node
	assert.Equal(t, "idemail", wfs[2].EditorItemId)
	assert.Len(t, wfs[2].Steps, 1)
	assert.Nil(t, wfs[2].Steps[0].NextFlow) // email -> ()
}
