package generator

import (
	ws "clever-monitor/workflow/pkg/workflowservice/clevermonitor/analytics/protos/workflow"
)

func PopulateCondition(cc *composedConfig, all []*composedConfig) error {
	err := PopulateDefault(cc, all)
	if err != nil {
		return err
	}

	for _, step := range cc.wfc.Steps {
		step.StepId = findItemById(step.NextFlow.Id, all).wfc.Id
		step.Conditions = []*ws.WorkflowConfig_Step_Condition{}

		// todo - iterate ec
		// todo - mapa pro operator (napr. "e" => "=" apod)

		//cond := &ws.WorkflowConfig_Step_Condition{
		//	Variable: cc.ec.Settings.Condition.Conditions[0].Field,
		//	Condition: fmt.Sprintf(
		//		"%s %s %s",
		//		cc.ec.Settings.Condition.Conditions[0].Field,
		//		cc.ec.Settings.Condition.Conditions[0].Operator,
		//		cc.ec.Settings.Condition.Conditions[0].Value,
		//	),
		//}
		//
		//step.Conditions = append(step.Conditions, cond)
	}

	return nil
}
