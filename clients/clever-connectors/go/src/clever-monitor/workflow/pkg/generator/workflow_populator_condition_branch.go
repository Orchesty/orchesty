package generator

import (
	"fmt"
	ws "clever-monitor/workflow/pkg/workflowservice/clevermonitor/analytics/protos/workflow"
)

// populateConditionBranch joins conditionsBranches parent with it's child
func populateConditionBranch(condType bool, cc *composedConfig, all []*composedConfig) error {
	parent := findParentItem(cc.ec, all)
	if parent == nil {
		return fmt.Errorf("condition branch should never be the root")
	}

	relatedStep, err := findParentalStep(cc, parent)
	if err != nil {
		return err
	}

	if condType == false {
		relatedStep.ConditionOpt.OptionType = ws.ConditionType_ELSE
	}

	child := findFirstChildItem(cc.ec, all)
	if child == nil {
		relatedStep.NextFlow = nil
	} else {
		relatedStep.NextFlow.Id = child.wfc.Id
	}

	return nil
}

// findParentalStep finds corresponding parental Step for current composedConfig
func findParentalStep(cc *composedConfig, parent *composedConfig) (*ws.WorkflowConfig_Step, error) {
	for _, parStep := range parent.wfc.Steps {
		if parStep.NextFlow.Id == cc.wfc.Id {
			return parStep, nil
		}
	}

	return nil, fmt.Errorf("unable to find parental condition step for condition branch")
}
