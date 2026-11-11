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
	Application() string
	Sdk() string
	TopologyName() string
	WorkerType() enum.WorkerType
}

// FollowerRouter is implemented by the live bridge node so Confirm can pick
// the Yes / No branch without publishing to every follower.
type FollowerRouter interface {
	FollowerIdsForOutput(output string) string
}
