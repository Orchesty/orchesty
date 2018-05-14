package generator

import ws "clever-monitor/workflow/pkg/workflowservice/clevermonitor/analytics/protos/workflow"

// populateDistribute fills workflowConfig struct with distribute related values
func populateDistribute(cc *composedConfig, all []*composedConfig) error {
	for _, step := range cc.wfc.Steps {
		action := &ws.WorkflowConfig_Step_ChannelMap_Action{
			ActionFamily: ws.WorkflowConfig_Step_ChannelMap_Action_LIST,
			ActionSubject: cc.ec.Settings.Distribute.ListId,
			ActionType: cc.ec.Settings.Distribute.Type,
		}

		step.Channels = &ws.WorkflowConfig_Step_ChannelMap{}
		step.Channels.Actions = append(step.Channels.Actions, action)
	}

	return nil
}
