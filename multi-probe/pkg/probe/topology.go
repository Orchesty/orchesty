package probe

type TopologyBridgeDebug struct {
	Port int    `json:"port,omitempty"`
	Host string `json:"host,omitempty"`
	Url  string `json:"url,omitempty"`
}

type TopologyBridgeLabel struct {
	ID       string `json:"id"`
	NodeId   string `json:"node_id"`
	NodeName string `json:"node_name"`
}

type TopologyBridgeJson struct {
	ID     string              `json:"id"`
	Label  TopologyBridgeLabel `json:"label"`
	Worker interface{}         `json:"worker"`
	Next   []string            `json:"next"`
	Debug  TopologyBridgeDebug `json:"debug"`
}

type TopologyJson struct {
	ID           string               `json:"id"`
	TopologyId   string               `json:"topology_id"`
	TopologyName string               `json:"topology_name"`
	Bridges      []TopologyBridgeJson `json:"nodes"`
}
