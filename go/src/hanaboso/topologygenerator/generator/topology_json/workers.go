package topology_json

import (
	"fmt"
	"hanaboso/utils/topology"
)

const WORKERBATCH = "batch"
const BATCHCONNECTOR = "batch_connector"
const CRON = "cron"
const DEBUG = "debug"
const RESEQUENCER = "resequencer"
const SPLITTER = "splitter"
const XMLPARSER = "xml_parser"
const FTP = "ftp"
const EMAIL = "email"
const MAPPER = "mapper"
const API = "api"
const CONNECTOR = "connector"
const WEBHOOK = "webhook"
const CUSTOM = "custom"
const SIGNAL = "signal"
const START = "start"
const GATEWAY = "gateway"

func getAmqRpc(node topology.Node) topology.TopologyBridgeWorkerJson {
	return topology.TopologyBridgeWorkerJson{
		Type: "splitter.amqprpc",
		Settings: topology.TopologyBridgeWorkerSettingsJson{
			PublishQueue: topology.TopologyBridgeWorkerSettingsQueueJson{
				Name:    fmt.Sprintf("pipes.%s", node.Type),
				Options: "",
			},
		},
	}
}

func getNull(node topology.Node) topology.TopologyBridgeWorkerJson {
	return topology.TopologyBridgeWorkerJson{
		Type: "worker.null",
	}
}

func getResequencer(node topology.Node) topology.TopologyBridgeWorkerJson {
	return topology.TopologyBridgeWorkerJson{
		Type: "worker.resequencer",
	}
}

func getSplitter(node topology.Node) topology.TopologyBridgeWorkerJson {
	return topology.TopologyBridgeWorkerJson{
		Type: "splitter.json",
	}
}

func getXmlParser(node topology.Node) topology.TopologyBridgeWorkerJson {
	return topology.TopologyBridgeWorkerJson{
		Type:     "worker.http_xml_parser",
		Settings: getHttpWorkerSettings(node),
	}
}

func getHttp(node topology.Node) topology.TopologyBridgeWorkerJson {
	return topology.TopologyBridgeWorkerJson{
		Type:     "worker.http",
		Settings: getHttpWorkerSettings(node),
	}
}

func getHttpWorkerSettings(n topology.Node) topology.TopologyBridgeWorkerSettingsJson {
	return topology.TopologyBridgeWorkerSettingsJson{
		Host:         getHost(n.Type),
		ProcessPath:  fmt.Sprintf("/%s", getRoute(n.Type, n.Name)),
		StatusPath:   fmt.Sprintf("/%s/test", getRoute(n.Type, n.Name)),
		Method:       "POST",
		Port:         80,
		Secure:       false,
		Opts:         make([]string, 0),
		PublishQueue: topology.TopologyBridgeWorkerSettingsQueueJson{},
	}
}
