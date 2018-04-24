package generator

import ws "clever-monitor/workflow/pkg/workflowservice"

func PopulateDefault(cc *composedConfig, all []*composedConfig) error {
	// Add step field
	followers := findChildItems(cc.ec, all)
	for _, follower := range followers {
		step := &ws.WorkflowConfig_Step{
			Condition: "true",
			NextFlow: &ws.WorkflowConfig_Step_NextFlow{
				Id: follower.wfc.Id,
				Wait: int32(0), // TODO - where to get?
			},
		}

		cc.wfc.Steps = append(cc.wfc.Steps, step)
	}

	return nil
}
