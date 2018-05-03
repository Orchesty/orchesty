package generator

import ws "clever-monitor/workflow/pkg/workflowservice/clevermonitor/analytics/protos/workflow"

func PopulateNotify(cc *composedConfig, all []*composedConfig) error {
	err := PopulateDefault(cc, all)
	if err != nil {
		return err
	}

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
