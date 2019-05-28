package model

import (
	"fmt"

	"go.mongodb.org/mongo-driver/bson/primitive"
)

type TopologyBridgeDebugJson struct {
	Port int    `json:"port,omitempty"`
	Host string `json:"host,omitempty"`
	Url  string `json:"url,omitempty"`
}

type TopologyBridgeLabelJson struct {
	ID       string `json:"id"`
	NodeId   string `json:"node_id"`
	NodeName string `json:"node_name"`
}

type TopologyBridgeWorkerSettingsQueueJson struct {
	Name    string `json:"name,omitempty"`
	Options string `json:"options,omitempty"`
}

type TopologyBridgeWorkerSettingsJson struct {
	Host           string                                `json:"host,omitempty"`
	ProcessPath    string                                `json:"process_path,omitempty"`
	StatusPath     string                                `json:"status_path,omitempty"`
	Method         string                                `json:"method,omitempty"`
	Port           int                                   `json:"port,omitempty"`
	Secure         bool                                  `json:"secure,omitempty" default:"true"`
	Opts           []string                              `json:"opts,omitempty"`
	PublishQueue   TopologyBridgeWorkerSettingsQueueJson `json:"publish_queue,omitempty"`
	ParserSettings []string                              `json:"parser_settings,omitempty"`
}

type TopologyBridgeWorkerJson struct {
	Type     string                           `json:"type"`
	Settings TopologyBridgeWorkerSettingsJson `json:"settings,omitempty"`
}

type TopologyBridgeJson struct {
	ID     string                           `json:"id"`
	Label  TopologyBridgeLabelJson          `json:"label"`
	Faucet TopologyBridgeFaucetSettingsJson `json:"faucet"`
	Worker TopologyBridgeWorkerJson         `json:"worker"`
	Next   []string                         `json:"next"`
	Debug  TopologyBridgeDebugJson          `json:"debug"`
}

type TopologyJson struct {
	ID           string               `json:"id"`
	TopologyId   string               `json:"topology_id"`
	TopologyName string               `json:"topology_name"`
	Bridges      []TopologyBridgeJson `json:"nodes"`
}

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

type TopologyBridgeFaucetSettingsJson struct {
	Settings map[string]int `json:"settings,omitempty"`
}

type NodeNext struct {
	ID   string `bson:"id"`
	Name string `bson:"name"`
}

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

func (n *Node) GetServiceName() string {
	//TODO: add webalize to Name
	return fmt.Sprintf("%s-%s", n.ID.Hex(), n.Name)
}

func (n *Node) GetNext() []string {

	var nextNode []string
	nextNode = make([]string, 0)

	for _, next := range n.Next {
		nextNode = append(nextNode, CreateServiceName(fmt.Sprintf("%s-%s", next.ID, next.Name)))
	}

	return nextNode
}

func (t *Topology) NormalizeName() string {
	//TODO: add webalize to Name
	return fmt.Sprintf("%s-%s", t.ID.Hex(), t.Name)
}

func (t *Topology) GetDockerName() string {
	//TODO: add dockerize
	return fmt.Sprintf("%s%s", t.ID.Hex(), t.Name)
}

func (t *Topology) GetMultiNodeName() string {
	return fmt.Sprintf("%s_mb", t.ID.Hex())
}

func (t *Topology) GetSaveDir() string {
	return t.NormalizeName()
}

func (t *Topology) GetSwarmName(prefix string) string {
	return fmt.Sprintf("%s_%s", prefix, Substring(t.ID.Hex(), 8, len(t.ID.Hex())))
}

func (t *Topology) GetProbeServiceName() string {
	return fmt.Sprintf("%s_probe", t.ID.Hex())
}

func (t *Topology) GetCounterServiceName() string {
	return fmt.Sprintf("%s_counter", t.ID.Hex())
}

func (t *Topology) GetConfigName(prefix string) string {
	return fmt.Sprintf(
		"%s_%s_config",
		prefix,
		Substring(t.ID.Hex(), 8, len(t.ID.Hex())),
	)
}

func (t *Topology) GetTopologyPrefix(prefix string) string {
	return fmt.Sprintf(
		"%s_%s",
		prefix,
		Substring(t.ID.Hex(), 8, len(t.ID.Hex())),
	)
}

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
