package model

import (
	"fmt"
	"strings"

	"go.mongodb.org/mongo-driver/bson/primitive"
)

// TopologyBridgeDebugJSON TopologyBridgeDebugJSON
type TopologyBridgeDebugJSON struct {
	Port int    `json:"port,omitempty"`
	Host string `json:"host,omitempty"`
	URL  string `json:"url,omitempty"`
}

// TopologyBridgeLabelJSON TopologyBridgeLabelJSON
type TopologyBridgeLabelJSON struct {
	ID       string `json:"id"`
	NodeID   string `json:"node_id"`
	NodeName string `json:"node_name"`
}

// TopologyBridgeWorkerSettingsQueueJSON TopologyBridgeWorkerSettingsQueueJSON
type TopologyBridgeWorkerSettingsQueueJSON struct {
	Name    string `json:"name,omitempty"`
	Options string `json:"options,omitempty"`
}

// TopologyBridgeWorkerSettingsJSON TopologyBridgeWorkerSettingsJSON
type TopologyBridgeWorkerSettingsJSON struct {
	Host           string                                `json:"host,omitempty"`
	ProcessPath    string                                `json:"process_path,omitempty"`
	StatusPath     string                                `json:"status_path,omitempty"`
	Method         string                                `json:"method,omitempty"`
	Port           int                                   `json:"port,omitempty"`
	Secure         bool                                  `json:"secure,omitempty" default:"true"`
	Opts           []string                              `json:"opts,omitempty"`
	PublishQueue   TopologyBridgeWorkerSettingsQueueJSON `json:"publish_queue,omitempty"`
	ParserSettings []string                              `json:"parser_settings,omitempty"`
}

// TopologyBridgeWorkerJSON TopologyBridgeWorkerJSON
type TopologyBridgeWorkerJSON struct {
	Type     string                           `json:"type"`
	Settings TopologyBridgeWorkerSettingsJSON `json:"settings,omitempty"`
}

// TopologyBridgeJSON TopologyBridgeJSON
type TopologyBridgeJSON struct {
	ID     string                           `json:"id"`
	Label  TopologyBridgeLabelJSON          `json:"label"`
	Faucet TopologyBridgeFaucetSettingsJSON `json:"faucet"`
	Worker TopologyBridgeWorkerJSON         `json:"worker"`
	Next   []string                         `json:"next"`
	Debug  TopologyBridgeDebugJSON          `json:"debug"`
}

// TopologyJSON TopologyJSON
type TopologyJSON struct {
	ID           string               `json:"id"`
	TopologyID   string               `json:"topology_id"`
	TopologyName string               `json:"topology_name"`
	Bridges      []TopologyBridgeJSON `json:"nodes"`
}

// Topology Topology
type Topology struct {
	ID         primitive.ObjectID `bson:"_id"`
	Name       string             `bson:"name"`
	Version    int                `bson:"version"`
	Descr      string             `bson:"descr"`
	Visibility string             `bson:"visibility"`
	Status     string             `bson:"status"`
	Enabled    bool               `bson:"enabled"`
	Bpmn       string             `bson:"bpmn"`
	RawBpmn    string             `bson:"rawBpmn"`
	Deleted    bool               `bson:"deleted"`
}

// TopologyBridgeFaucetSettingsJSON TopologyBridgeFaucetSettingsJSON
type TopologyBridgeFaucetSettingsJSON struct {
	Settings map[string]int `json:"settings,omitempty"`
}

// NodeNext NodeNext
type NodeNext struct {
	ID   string `bson:"id"`
	Name string `bson:"name"`
}

// Node Node
type Node struct {
	ID       primitive.ObjectID `bson:"_id"`
	Name     string             `bson:"name"`
	Topology string             `bson:"topology"`
	Next     []NodeNext         `bson:"next"`
	Type     string             `bson:"type"`
	Handler  string             `bson:"handler"`
	Enabled  bool               `bson:"enabled"`
	Deleted  bool               `bson:"deleted"`
}

// GetServiceName GetServiceName
func (n *Node) GetServiceName() string {
	//TODO: add webalize to Name
	return fmt.Sprintf("%s-%s", n.ID.Hex(), n.Name)
}

// GetNext GetNext
func (n *Node) GetNext() []string {

	var nextNode []string
	nextNode = make([]string, 0)

	for _, next := range n.Next {
		nextNode = append(nextNode, CreateServiceName(fmt.Sprintf("%s-%s", next.ID, next.Name)))
	}

	return nextNode
}

// NormalizeName NormalizeName
func (t *Topology) NormalizeName() string {
	//TODO: add webalize to Name
	return fmt.Sprintf("%s-%s", t.ID.Hex(), t.Name)
}

// GetDockerName GetDockerName
func (t *Topology) GetDockerName() string {
	return fmt.Sprintf("%s-%s", t.ID.Hex(), strings.ToLower(strings.ReplaceAll(t.Name, " ", "")))
}

// GetMultiNodeName GetMultiNodeName
func (t *Topology) GetMultiNodeName() string {
	return fmt.Sprintf("mb-%s", t.ID.Hex())
}

// GetSaveDir GetSaveDir
func (t *Topology) GetSaveDir() string {
	return t.NormalizeName()
}

// GetSwarmName GetSwarmName
func (t *Topology) GetSwarmName(prefix string) string {
	return fmt.Sprintf("%s_%s", prefix, Substring(t.ID.Hex(), 8, len(t.ID.Hex())))
}

// GetProbeServiceName GetProbeServiceName
func (t *Topology) GetProbeServiceName() string {
	return fmt.Sprintf("%s_probe", t.ID.Hex())
}

// GetCounterServiceName GetCounterServiceName
func (t *Topology) GetCounterServiceName() string {
	return fmt.Sprintf("%s_counter", t.ID.Hex())
}

// GetConfigName GetConfigName
func (t *Topology) GetConfigName(prefix string) string {
	return fmt.Sprintf(
		"%s_%s_config",
		prefix,
		Substring(t.ID.Hex(), 8, len(t.ID.Hex())),
	)
}

// GetTopologyPrefix GetTopologyPrefix
func (t *Topology) GetTopologyPrefix(prefix string) string {
	return fmt.Sprintf(
		"%s_%s",
		prefix,
		Substring(t.ID.Hex(), 8, len(t.ID.Hex())),
	)
}

// GetVolumes GetVolumes
func (t *Topology) GetVolumes(adapter Adapter, sourcePath string, topologyPath string) []string {
	var volumes = make([]string, 0)

	if adapter == ModeCompose {
		volumes = append(volumes, fmt.Sprintf(
			"%s/%s/topology.json:%s",
			sourcePath,
			t.GetSaveDir(),
			topologyPath,
		))
	}

	return volumes
}
