package types

import (
	"github.com/hanaboso/pipes/bridge/pkg/enum"
	"github.com/hanaboso/pipes/bridge/pkg/model"
)

type Node interface {
	Id() string
	Followers() Publishers
	Settings() model.NodeSettings
	CursorPublisher() Publisher
	NodeName() string
	TopologyName() string
	RepeaterSettings() model.NodeSettingsRepeater
	LimiterSettings() model.NodeSettingsLimiter
	WorkerType() enum.WorkerType
}
