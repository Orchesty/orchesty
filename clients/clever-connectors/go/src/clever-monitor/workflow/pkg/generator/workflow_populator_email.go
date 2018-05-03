package generator

import ws "clever-monitor/workflow/pkg/workflowservice/clevermonitor/analytics/protos/workflow"

func PopulateEmail(cc *composedConfig, all []*composedConfig) error {
	err := PopulateDefault(cc, all)
	if err != nil {
		return err
	}

	for _, step := range cc.wfc.Steps {
		step.Channels = &ws.WorkflowConfig_Step_ChannelMap{
			Email: &ws.WorkflowConfig_Step_ChannelMap_Email{
				Subject: cc.ec.Settings.Email.Subject,
				SenderEmail: cc.ec.Settings.Email.SenderEmail,
				SenderName: cc.ec.Settings.Email.SenderName,
				TemplateId: cc.ec.Settings.Email.TemplateId,
			},
		}
	}

	return nil
}
