package types

import "github.com/hanaboso/pipes/bridge/pkg/model"

// Separated to avoid Go' complains about import cycle

type Worker interface {
	BeforeProcess(node Node, dto *model.ProcessMessage) model.ProcessResult
	AfterProcess(node Node, dto *model.ProcessMessage) (model.ProcessResult, int)
}
