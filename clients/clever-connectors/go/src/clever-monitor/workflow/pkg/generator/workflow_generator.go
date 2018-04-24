package generator

import ws "clever-monitor/workflow/pkg/workflowservice"

type workflowGenerator struct {}

func NewWorkflowGenerator() *workflowGenerator {
	return &workflowGenerator{}
}

func (gen *workflowGenerator) Generate(editor *ws.EditorConfig) ([]*ws.WorkflowConfig, error) {
	var wfs []*ws.WorkflowConfig

	return wfs, nil
}
