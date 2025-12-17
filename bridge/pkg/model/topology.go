package model

import (
	"time"

	"github.com/hanaboso/pipes/bridge/pkg/enum"

	"github.com/rs/zerolog"
)

type Topology struct {
	ID      string
	Name    string
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

type TopologyV2 struct {
	Id       string   `json:"id"`
	Name     string   `json:"name"`
	Nodes    []NodeV2 `json:"nodes"`
	RabbitMq []TopologyV2RabbitMq
}

type TopologyV2RabbitMq struct {
	Dsn string `json:"dsn"`
}

// Adds topologyId -> best to use as .EmbedObject(t)
func (t Topology) MarshalZerologObject(e *zerolog.Event) {
	e.Str(enum.LogHeader_TopologyId, t.ID)
}
