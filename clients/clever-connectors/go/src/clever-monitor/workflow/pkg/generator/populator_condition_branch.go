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

	relatedStep := findParentalStep(cc, parent)
	if relatedStep == nil {
		return fmt.Errorf("unable to find parental condition step for condition branch")
	}

	relatedStep.StepId = cc.ec.Id
	if condType == false {
		if relatedStep.ConditionOpt == nil {
			relatedStep.ConditionOpt = &ws.WorkflowConfig_Step_Option{}
		}

		relatedStep.ConditionOpt.OptionType = ws.ConditionType_ELSE
	}

	child := findFirstChildItem(cc, all)
	if child == nil {
		relatedStep.NextFlow = nil
	} else {
		relatedStep.NextFlow.Id = child.wfc.Id
	}

	return nil
}
