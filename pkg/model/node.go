package model

import "github.com/hanaboso/pipes/bridge/pkg/enum"

// Worker node
type Node struct {
	ID        string
	Name      string
	Worker    enum.WorkerType
	Followers []string
	Messages  chan ProcessDto
}

type NodeShard struct {
	RabbitMQDSN string
	Index       int
	Node        *Node
}

/** Deprecated v1 .json format **/

type NodeV1 struct {
	Id     string      `json:"id"`
	Label  NodeLabelV1 `json:"label"`
	Worker WorkerV1    `json:"worker"`
	Next   []string    `json:"next"`
	Debug  NodeDebugV1 `json:"debug"`
	Faucet struct{}    // TODO ??
}

type NodeLabelV1 struct {
	Id       string `json:"id"`
	NodeID   string `json:"node_id"`
	NodeName string `json:"node_name"`
}

type WorkerV1 struct {
	Type     string           `json:"type"`
	Settings WorkerSettingsV1 `json:"settings"`
}

type WorkerSettingsV1 struct {
	Host         string   `json:"host"`
	ProcessPath  string   `json:"process_path"`
	StatusPath   string   `json:"status_path"`
	Method       string   `json:"method"`
	Port         int      `json:"port"`
	PublishQueue struct{} // ??
}

type NodeDebugV1 struct {
	Port int    `json:"port"`
	Host string `json:"host"`
	Url  string `json:"url"`
}
