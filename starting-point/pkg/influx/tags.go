package influx

import (
	"os"
	"starting-point/pkg/storage"
)

const host = "host"
const topologyID = "topology_id"
const nodeID = "node_id"
const correlationID = "correlation_id"

// GetTags get tags structure for influx sender
func GetTags(topology storage.Topology, correlation string) (m map[string]interface{}) {
	m = make(map[string]interface{})
	h, err := os.Hostname()
	if err != nil {
		h = "unknown"
	}

	m[host] = h
	m[topologyID] = topology.ID.Hex()
	m[nodeID] = topology.Node.ID.Hex()
	m[correlationID] = correlation

	return
}
