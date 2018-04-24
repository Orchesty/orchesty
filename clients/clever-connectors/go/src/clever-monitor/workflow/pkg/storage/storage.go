package storage

type Storage interface {
	Finder
	Creator
	Deleter
}

type Finder interface {
	FindEditorConfig(id string) (*EditorRecord, error)
	FindWorkflowConfig(id string) (*WorkflowRecord, error)
	FindAllWorkflowConfigs(editorId string) ([]*WorkflowRecord, error)
}

type Creator interface {
	Create(editorConfig string, workflowConfigs []string) (string, error)
}

type Deleter interface {
	Delete(editorConfigId string) (error)
}
