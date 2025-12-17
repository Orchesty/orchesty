package model

import (
	"fmt"
	"github.com/hanaboso/pipes/bridge/pkg/enum"
	"github.com/rs/zerolog"
)

// Worker node
type Node struct {
	ID          string
	Name        string
	Application string
	Worker      enum.WorkerType
	Settings    NodeSettings
	Followers   []Follower
}

type NodeShard struct {
	RabbitMQDSN string
	Index       int
	Node        *Node
}

type Follower struct {
	Id   string `json:"id"`
	Name string `json:"name"`
}

type NodeSettings struct {
	Url        string
	ActionPath string
	Headers    map[string]interface{}
	// Side bar settings
	Bridge NodeSettingsBridge
}

type NodeSettingsBridge struct {
	Prefetch int
	Timeout  int // seconds
}

func (n NodeSettings) ActionUrl() string {
	return fmt.Sprintf("%s/%s", n.Url, n.ActionPath)
}

/** Deprecated v1 .json format **/

type NodeV1 struct {
	Id     string      `json:"id"`
	Label  NodeLabelV1 `json:"label"`
	Worker WorkerV1    `json:"worker"`
	Next   []string    `json:"next"`
	Debug  NodeDebugV1 `json:"debug"`
	Faucet struct{}
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

type NodeV2 struct {
	Id          string           `json:"id"`
	Name        string           `json:"name"`
	Application string           `json:"application"`
	Worker      enum.WorkerType  `json:"worker"`
	Settings    NodeV2Settings   `json:"settings"`
	Followers   []NodeV2Follower `json:"followers"`
}

type NodeV2Follower struct {
	Id   string `json:"id"`
	Name string `json:"name"`
}

type NodeV2Settings struct {
	Url        string                 `json:"url"`
	ActionPath string                 `json:"actionPath"`
	Method     string                 `json:"method"`
	Headers    map[string]interface{} `json:"headers"`
	// Bridge
	Timeout        int `json:"timeout"`
	RabbitPrefetch int `json:"rabbitPrefetch"`
}

// Adds nodeId - use as .EmbedObject(s)
func (s NodeShard) MarshalZerologObject(e *zerolog.Event) {
	e.Str(enum.LogHeader_NodeId, s.Node.ID)
	e.Interface(enum.LogHeader_Data, LogData{
		"rabbitMqDsn": s.RabbitMQDSN,
	})
}
