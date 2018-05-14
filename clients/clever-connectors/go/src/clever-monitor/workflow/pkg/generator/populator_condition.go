package generator

import (
	ws "clever-monitor/workflow/pkg/workflowservice/clevermonitor/analytics/protos/workflow"
	"fmt"
)

// PopulateCondition populates the workflow config item according to relevant editor config item
func populateCondition(cc *composedConfig, all []*composedConfig) error {
	// transform condition fields
	for _, step := range cc.wfc.Steps {
		if step.ConditionOpt == nil {
			step.ConditionOpt = &ws.WorkflowConfig_Step_Option{
				OptionType: cc.ec.Settings.Condition.Type,
			}
		}
		step.Conditions = []*ws.WorkflowConfig_Step_Condition{}

		for _, editorCond := range cc.ec.Settings.Condition.Conditions {
			cond := &ws.WorkflowConfig_Step_Condition{}
			cond.Variable = editorCond.Field
			cond.Condition = joinCondition(editorCond.Operator, editorCond.Value)

			step.Conditions = append(step.Conditions, cond)
		}
	}

	return nil
}

// joinCondition makes string that expresses the condition parts with single string
func joinCondition(operator string, value string) string {
	return fmt.Sprintf("%s %s %s", "x", translateOperator(operator), value)
}

// translateOperator converts symbols for conditional operators
func translateOperator(operator string) string {
	switch operator {
	case "eq":
		return "=="
	case "ne":
		return "!="
	case "gt":
		return ">"
	case "gte":
		return ">="
	case "lt":
		return "<"
	case "lte":
		return "<="
	default:
		return operator
	}

}
