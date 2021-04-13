package model

import (
	"github.com/hanaboso/pipes/bridge/pkg/enum"
	"time"

	"github.com/rs/zerolog"
)

type Topology struct {
	ID      string
	Nodes   []Node
	Shards  []NodeShard
	Timeout time.Duration
}

/** Deprecated v1 .json format **/

type TopologyV1 struct {
	ID           string   `json:"id"`
	TopologyId   string   `json:"topology_id"`
	TopologyName string   `json:"topology_name"`
	Nodes        []NodeV1 `json:"nodes"`
}

// Adds topologyId -> best to use as .EmbedObject(t)
func (t Topology) MarshalZerologObject(e *zerolog.Event) {
	e.Str(enum.LogHeader_TopologyId, t.ID)
}
