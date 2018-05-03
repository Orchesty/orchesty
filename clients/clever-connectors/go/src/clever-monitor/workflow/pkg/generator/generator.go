package generator

import ws "clever-monitor/workflow/pkg/workflowservice/clevermonitor/analytics/protos/workflow"

type Generator interface {
	Generate(editor *ws.EditorConfig, clientId int, guid string) ([]*ws.WorkflowConfig, error)
}