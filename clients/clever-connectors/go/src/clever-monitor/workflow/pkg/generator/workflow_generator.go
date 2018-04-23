package generator

import ws "clever-monitor/workflow/pkg/workflowservice"

type workflowGenerator struct {}

func NewWorkflowGenerator() *workflowGenerator {
	return &workflowGenerator{}
}

func (gen *workflowGenerator) Generate(editor *ws.EditorConfig) (map[string]ws.WorkflowConfig, error) {
	wfs := make(map[string]ws.WorkflowConfig)

	return wfs, nil
}

func (gen *workflowGenerator) GenerateStrings(editor *ws.EditorConfig) (map[string]string, error) {
	wfs := make(map[string]string)

	return wfs, nil
}
