package probe

type topologyBridgeDebugJson struct {
	Port int
	Host string
	Url  string
}

type topologyBridgeLabelJson struct {
	ID       string
	NodeId   string `json:"node_id"`
	NodeName string `json:"node_name"`
}

type topologyBridgeJson struct {
	ID    string
	Label topologyBridgeLabelJson
	Debug topologyBridgeDebugJson
	Next  []string
}

type TopologyJson struct {
	ID           string
	TopologyId   string               `json:"topology_id"`
	TopologyName string               `json:"topology_name"`
	Bridges      []topologyBridgeJson `json:"nodes"`
}
