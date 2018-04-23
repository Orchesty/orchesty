package storage

type Storage interface {
	Finder
	Creator
	Deleter
}

type Finder interface {
	FindEditorConfig(id string) (string, error)
	FindWorkflowConfig(id string) (string, error)
}

type Creator interface {
	Create(editorConfig string, workflowConfigs map[string]string) (string, error)
}

type Deleter interface {
	Delete(editorConfigId string) (error)
}
