package generator

import ws "clever-monitor/workflow/pkg/workflowservice"

type Generator interface {
	Generate(editor *ws.EditorConfig) (map[string]ws.WorkflowConfig, error)
	GenerateStrings(editor *ws.EditorConfig) (map[string]string, error)
}