package generator

import (
	ws "clever-monitor/workflow/pkg/workflowservice/clevermonitor/analytics/protos/workflow"
	"fmt"
)

// PopulateCondition populates the workflow config item according to relevant editor config item
func populateCondition(cc *composedConfig, all []*composedConfig) error {
	err := populateDefault(cc, all)
	if err != nil {
		return err
	}

	// transform condition fields
	for _, step := range cc.wfc.Steps {
		step.StepId = findItemById(step.NextFlow.Id, all).ec.Id
		step.Conditions = []*ws.WorkflowConfig_Step_Condition{}

		for _, editorCond := range cc.ec.Settings.Condition.Conditions {
			cond := &ws.WorkflowConfig_Step_Condition{}
			cond.Variable = editorCond.Field
			cond.Condition = joinCondition(editorCond.Field, editorCond.Operator, editorCond.Value)

			step.Conditions = append(step.Conditions, cond)
		}
	}

	return nil
}

// joinCondition makes string that expresses the condition parts with single string
func joinCondition(field string, operator string, value string) string {
	return fmt.Sprintf("%s %s %s", field, translateOperator(operator), value)
}

// translateOperator converts symbols for conditional operators
func translateOperator(operator string) string {
	switch operator {
	case "e":
		return "=="
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
