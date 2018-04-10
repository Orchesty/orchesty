package topology_json

import (
	"encoding/json"
	"fmt"
	"strings"

	"hanaboso/topologygenerator/model"
	"hanaboso/utils/servicename"
	"hanaboso/utils/topology"
)

const DEFAULTPORT = 8008

func Create(te *topology.Topology, node []topology.Node) ([]byte, error) {

	var bridges = getBridges(te, node)

	t := topology.TopologyJson{
		ID:           servicename.CreateServiceName(te.NormalizeName()),
		TopologyName: te.Name,
		TopologyId:   te.ID.Hex(),
		Bridges:      bridges,
	}

	bytes, err := json.Marshal(t)

	return bytes, err
}

func getBridges(te *topology.Topology, nodes []topology.Node) []topology.TopologyBridgeJson {

	var (
		bridges []topology.TopologyBridgeJson
		port    int
	)

	i := 0
	for _, node := range nodes {

		port = DEFAULTPORT + i

		nodeId := servicename.CreateServiceName(node.GetServiceName())

		bridges = append(bridges, topology.TopologyBridgeJson{
			ID: servicename.CreateServiceName(nodeId),
			Label: topology.TopologyBridgeLabelJson{
				ID:       servicename.CreateServiceName(nodeId),
				NodeId:   node.ID.Hex(),
				NodeName: node.Name,
			},
			Worker: getWorkers(node),
			Next:   node.GetNext(),
			// TODO: add multimode choice
			Debug: topology.TopologyBridgeDebugJson{
				Port: port,
				Host: te.GetMultiNodeName(),
				Url:  fmt.Sprintf("http://%s:%d/status", te.GetMultiNodeName(), port),
			},
		})

		i++
	}

	return bridges
}

func getWorkers(n topology.Node) topology.TopologyBridgeWorkerJson {
	var worker topology.TopologyBridgeWorkerJson

	switch n.Type {
	case WORKERBATCH:
		worker = getAmqRpc(n)
	case BATCHCONNECTOR:
		worker = getAmqRpc(n)
	case CRON:
		worker = getNull(n)
	case DEBUG:
		worker = getNull(n)
	case START:
		worker = getNull(n)
	case GATEWAY:
		worker = getNull(n)
	case RESEQUENCER:
		worker = getResequencer(n)
	case SPLITTER:
		worker = getSplitter(n)
	case XMLPARSER:
		worker = getXmlParser(n)
	default:
		worker = getHttp(n)
	}

	return worker
}

func getHost(nodeType string) string {
	var host string

	switch nodeType {
	case XMLPARSER:
		host = "xml-parser-api"
	case FTP:
		host = "ftp-api"
	case EMAIL:
		host = "mailer-api"
	case MAPPER:
		host = "mapper-api"
	case API:
		host = "monolith-api"
	case CONNECTOR:
		host = "monolith-api"
	case WEBHOOK:
		host = "monolith-api"
	case CUSTOM:
		host = "monolith-api"
	case SIGNAL:
		host = "monolith-api"
	default:
		panic(model.AppError{"Unknown type for host", model.HTTPWORKER})
	}

	return host
}

func getRoute(nodeType string, serviceId string) string {
	var url string

	switch nodeType {
	case CONNECTOR:
		url = "connector/{service_id}/action"
	case API:
		url = "connector/{service_id}/action"
	case FTP:
		url = "connector/{service_id}/action"
	case WEBHOOK:
		url = "connector/{service_id}/webhook"
	case MAPPER:
		url = "mapper/{service_id}"
	case XMLPARSER:
		url = "{service_id}"
	case EMAIL:
		url = "mailer/{service_id}"
	case CUSTOM:
		url = "custom_node/{service_id}/process"
	case SIGNAL:
		url = "custom_node/{service_id}/process"
	default:
		panic(model.AppError{"Unknown type for routing", model.HTTPWORKER})
	}

	return strings.Replace(url, "{service_id}", serviceId, -1)
}
