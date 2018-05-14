package generator

import (
	"testing"
	"github.com/stretchr/testify/assert"
	"clever-monitor/workflow/pkg/hydrator"
	ws "clever-monitor/workflow/pkg/workflowservice/clevermonitor/analytics/protos/workflow"
)

// Generate and check workflow configs from given editor config containing condition node
func TestWorkflowGenerator_Generate_Editor2(t *testing.T) {
	config, err := hydrator.StringToEditorConfig(getEditorJson(t, "editor_2.json"))
	assert.Nil(t, err)
	generator := NewRecursiveGenerator()

	wfs, err := generator.Generate(config, 555, "guid")
	assert.Nil(t, err)

	// Only config for selected items should be generated (some are skipped)
	assert.Len(t, wfs, 4)

	checkEditor2Sequence(t, wfs)

	checkEditor2Notify1Config(t, wfs[0])
	checkEditor2ConditionConfig(t, wfs[1])
	checkEditor2EmailConfig(t, wfs[2])
	checkEditor2Notify2Config(t, wfs[3])

	// save output to file in order to be check-able by human
	saveWorkflowConfigs(t, wfs, "workflow_2")
}

// check if the configs are ordered properly
func checkEditor2Sequence(t *testing.T, wfs []*ws.WorkflowConfig) {
	// notify node
	n1 := wfs[0]
	assert.Equal(t, "id2", n1.EditorItemId)
	assert.Len(t, n1.Steps, 1)
	assert.Equal(t, wfs[1].Id, n1.Steps[0].NextFlow.Id)

	// condition node
	cond := wfs[1]
	assert.Equal(t, "id3", cond.EditorItemId)
	assert.Len(t, cond.Steps, 2)
	assert.Equal(t, wfs[2].Id, cond.Steps[0].NextFlow.Id)
	assert.Equal(t, wfs[3].Id, cond.Steps[1].NextFlow.Id)

	// email node
	assert.Equal(t, "id6", wfs[2].EditorItemId)
	assert.Len(t, wfs[2].Steps, 1)
	assert.Nil(t, wfs[2].Steps[0].NextFlow)

	// notify node
	assert.Equal(t, "id7", wfs[3].EditorItemId)
	assert.Len(t, wfs[3].Steps, 1)
	assert.Nil(t, wfs[3].Steps[0].NextFlow)
}

// check the validity of the first notify item
func checkEditor2Notify1Config(t *testing.T, wf *ws.WorkflowConfig) {
	assert.Equal(t, false, wf.Steps[0].Channels.Notify.Email)
	assert.Equal(t, true, wf.Steps[0].Channels.Notify.Aim)
}

// check the validity of the condition item
func checkEditor2ConditionConfig(t *testing.T, wf *ws.WorkflowConfig) {
	assert.Equal(t, "id4", wf.Steps[0].StepId)
	assert.Equal(t, ws.ConditionType_AND, wf.Steps[0].ConditionOpt.OptionType)

	assert.Equal(t, "id5", wf.Steps[1].StepId)
	assert.Equal(t, ws.ConditionType_ELSE, wf.Steps[1].ConditionOpt.OptionType)

	for _, step := range wf.Steps {
		for _, cond := range step.Conditions {
			assert.Equal(t, "name", cond.Variable)
			assert.Equal(t, "x == 10", cond.Condition)
		}
	}
}

// check the validity of the email item
func checkEditor2EmailConfig(t *testing.T, wf *ws.WorkflowConfig) {
	assert.Equal(t, "", wf.Steps[0].Channels.Email.TemplateId)
	assert.Equal(t, "sender", wf.Steps[0].Channels.Email.SenderName)
	assert.Equal(t, "email", wf.Steps[0].Channels.Email.SenderEmail)
	assert.Equal(t, "", wf.Steps[0].Channels.Email.Subject)
}

// check the validity of the second notify item
func checkEditor2Notify2Config(t *testing.T, wf *ws.WorkflowConfig) {
	assert.Equal(t, true, wf.Steps[0].Channels.Notify.Email)
	assert.Equal(t, true, wf.Steps[0].Channels.Notify.Aim)
}