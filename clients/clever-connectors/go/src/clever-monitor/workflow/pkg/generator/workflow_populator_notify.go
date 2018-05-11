package generator

import ws "clever-monitor/workflow/pkg/workflowservice/clevermonitor/analytics/protos/workflow"

// populateEmail fills workflowConfig struct with notify related values
func populateNotify(cc *composedConfig, all []*composedConfig) error {
	for _, step := range cc.wfc.Steps {
		step.Channels = &ws.WorkflowConfig_Step_ChannelMap{
			Notify: &ws.WorkflowConfig_Step_ChannelMap_Notify{
				Aim: cc.ec.Settings.Notify.Aim,
				Email: cc.ec.Settings.Notify.Email,
			},
		}
	}

	return nil
}
