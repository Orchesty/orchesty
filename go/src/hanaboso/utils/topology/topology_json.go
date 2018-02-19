package topology

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
	Secure         bool                                  `json:"secure,omitempty",default:"true"`
	Opts           []string                              `json:"opts,omitempty"`
	PublishQueue   TopologyBridgeWorkerSettingsQueueJson `json:"publish_queue,omitempty"`
	ParserSettings []string                              `json:"parser_settings,omitempty"`
}

type TopologyBridgeWorkerJson struct {
	Type     string                           `json:"type"`
	Settings TopologyBridgeWorkerSettingsJson `json:"settings,omitempty"`
}

type TopologyBridgeJson struct {
	ID     string                   `json:"id"`
	Label  TopologyBridgeLabelJson  `json:"label"`
	Worker TopologyBridgeWorkerJson `json:"worker"`
	Next   []string                 `json:"next"`
	Debug  TopologyBridgeDebugJson  `json:"debug"`
}

type TopologyJson struct {
	ID           string               `json:"id"`
	TopologyId   string               `json:"topology_id"`
	TopologyName string               `json:"topology_name"`
	Bridges      []TopologyBridgeJson `json:"nodes"`
}
