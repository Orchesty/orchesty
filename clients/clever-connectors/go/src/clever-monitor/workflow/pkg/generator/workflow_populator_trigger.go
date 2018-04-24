package generator

import ws "clever-monitor/workflow/pkg/workflowservice"

func PopulateTrigger(cc *composedConfig, all []*composedConfig) error {
	filter := &ws.WorkflowConfig_Filter{}

	filter.InSegment = []string{} // TODO - where to get?
	filter.NotInSegment = []string{} // TODO - where to get?

	cc.wfc.Filter = filter

	return PopulateDefault(cc, all)
}
