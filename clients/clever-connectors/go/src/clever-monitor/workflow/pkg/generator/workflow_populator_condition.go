package generator

import ws "clever-monitor/workflow/pkg/workflowservice"

// TODO - test
func PopulateCondition(cc *composedConfig, all []*composedConfig) error {
	filter := &ws.WorkflowConfig_Filter{}

	filter.FilteringVariable = []string{} // TODO - where to get?

	cc.wfc.Filter = filter

	return PopulateDefault(cc, all)
}
