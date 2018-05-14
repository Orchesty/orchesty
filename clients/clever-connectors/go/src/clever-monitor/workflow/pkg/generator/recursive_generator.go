package generator

import (
	ws "clever-monitor/workflow/pkg/workflowservice/clevermonitor/analytics/protos/workflow"
	"fmt"
)

type recursiveGenerator struct{}

func NewRecursiveGenerator() *recursiveGenerator {
	return &recursiveGenerator{}
}

// Generate returns the slice of workflow configs based on given editorConfig
func (gen *recursiveGenerator) Generate(
	editor *ws.EditorConfig,
	clientId int,
	guid string,
) ([]*ws.WorkflowConfig, error) {
	pairs := pairConfigs(editor, clientId, guid)
	err := populateDefaults(pairs)
	if err != nil {
		return []*ws.WorkflowConfig{}, err
	}

	trigger := findTrigger(pairs)
	if trigger == nil {
		return []*ws.WorkflowConfig{}, fmt.Errorf("no trigger found")
	}

	err = gen.generateRecursively(trigger, pairs, trigger)
	if err != nil {
		return []*ws.WorkflowConfig{}, err
	}

	return gen.filterSkipped(pairs), nil
}

// generateRecursively processes tree of editorConfig node's
func (gen *recursiveGenerator) generateRecursively(
	cc *composedConfig,
	all []*composedConfig,
	trigger *composedConfig,
) error {
	for _, step := range cc.wfc.Steps {
		if step.NextFlow != nil {
			follower := findItemById(step.NextFlow.Id, all)
			err := gen.generateRecursively(follower, all, trigger)
			if err != nil {
				return err;
			}
		}
	}

	if trigger.ec.Settings.Trigger != nil && trigger.ec.Settings.Trigger.EventOptions != nil {
		cc.wfc.Type = trigger.ec.Settings.Trigger.EventOptions.Type
		cc.wfc.ClientDomain = trigger.ec.Settings.Trigger.EventOptions.ClientDomain
	}

	cc.wfc.EditorItemId = cc.ec.Id
	return populateSpecifics(cc, all)
}

// filterSkipped cleans the slice of composedConfig from those that should be skipped
func (gen *recursiveGenerator) filterSkipped(all []*composedConfig) []*ws.WorkflowConfig {
	var wfs []*ws.WorkflowConfig

	for _, cc := range all {
		if cc.skip == false {
			wfs = append(wfs, cc.wfc)
		}
	}

	return wfs
}
