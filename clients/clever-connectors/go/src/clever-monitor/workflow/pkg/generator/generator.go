package generator

import ws "clever-monitor/workflow/pkg/workflowservice"

type Generator interface {
	Generate(editor *ws.EditorConfig) ([]*ws.WorkflowConfig, error)
}