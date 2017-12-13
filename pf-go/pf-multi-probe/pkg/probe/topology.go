package probe

type topologyBridgeDebugJson struct {
	port string
	host string
	Url  string
}

type topologyBridgeJson struct {
	ID       string
	NodeId   string
	NodeName string
	Next     []string
	Debug    topologyBridgeDebugJson
}

type topologyJson struct {
	ID      string
	Bridges []topologyBridgeJson `json:"nodes"`
}
