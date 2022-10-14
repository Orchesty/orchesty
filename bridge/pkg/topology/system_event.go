package topology

import "github.com/hanaboso/pipes/bridge/pkg/utils/stringx"

var systemTopologies = []string{"system-events"}

func IsSystemTopology(topologyName string) bool {
	return stringx.InArray(systemTopologies, topologyName)
}
