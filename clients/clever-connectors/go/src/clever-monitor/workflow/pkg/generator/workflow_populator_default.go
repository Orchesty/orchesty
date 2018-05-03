package generator

import ws "clever-monitor/workflow/pkg/workflowservice/clevermonitor/analytics/protos/workflow"

// populateDefault fills default workflow config settings that are common for multiple different config types
func populateDefault(cc *composedConfig, all []*composedConfig) error {
	// Set common fields from trigger
	trigger := findTrigger(all)
	cc.wfc.Filter = &ws.WorkflowConfig_Filter{}
	cc.wfc.Filter.InSegments = trigger.ec.Settings.Trigger.Segments
	cc.wfc.Filter.InLists = trigger.ec.Settings.Trigger.Lists

	return populateDefaultSteps(cc, all)
}

// populateDefaultSteps fills the step slice with correct number of step structs
func populateDefaultSteps(cc *composedConfig, all []*composedConfig) error {
	followers := findChildItems(cc.ec, all)

	if len(followers) == 0 {
		step := createDefaultStep(cc, nil)
		cc.wfc.Steps = append(cc.wfc.Steps, step)

		return nil
	}

	for _, follower := range followers {
		step := createDefaultStep(cc, follower)
		cc.wfc.Steps = append(cc.wfc.Steps, step)
	}

	return nil
}

// createDefaultStep creates Step struct with default settings
func createDefaultStep(cc *composedConfig, follower *composedConfig) *ws.WorkflowConfig_Step {
	step := &ws.WorkflowConfig_Step{
		StepId: cc.ec.Id,
	}

	if follower != nil {
		step.NextFlow = &ws.WorkflowConfig_Step_NextFlow{
			Id: follower.wfc.Id,
		}
	}

	// Conditions are empty by default
	step.Conditions = []*ws.WorkflowConfig_Step_Condition{}

	return step
}
