package generator

import ws "clever-monitor/workflow/pkg/workflowservice/clevermonitor/analytics/protos/workflow"

// populateEmail fills workflowConfig struct with email related values
func populateEmail(cc *composedConfig, all []*composedConfig) error {
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
