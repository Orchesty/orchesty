package generator

import ws "clever-monitor/workflow/pkg/workflowservice/clevermonitor/analytics/protos/workflow"

type Generator interface {
	// Generate returns the slice of generated workflow config due to given editor config data
	Generate(editor *ws.EditorConfig, clientId int, guid string) ([]*ws.WorkflowConfig, error)
}