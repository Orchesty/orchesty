package generator

import ws "clever-monitor/workflow/pkg/workflowservice/clevermonitor/analytics/protos/workflow"

// TODO - test
func PopulateCondition(cc *composedConfig, all []*composedConfig) error {
	filter := &ws.WorkflowConfig_Filter{}

	cc.wfc.Filter = filter

	return PopulateDefault(cc, all)
}
