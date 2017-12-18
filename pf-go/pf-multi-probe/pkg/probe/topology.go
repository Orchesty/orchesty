package probe

type topologyBridgeDebugJson struct {
	port string
	host string
	Url  string
}

type topologyBridgeLabelJson struct {
	ID       string `json:"id"`
	NodeId   string `json:"node_id"`
	NodeName string `json:"node_name"`
}

type topologyBridgeJson struct {
	ID    string
	Label topologyBridgeLabelJson
	Debug topologyBridgeDebugJson
	Next  []string
}

type topologyJson struct {
	ID           string
	TopologyId   string               `json:"topology_id"`
	TopologyName string               `json:"topology_name"`
	Bridges      []topologyBridgeJson `json:"nodes"`
}
