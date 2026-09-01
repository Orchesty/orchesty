package topology

import "github.com/hanaboso/pipes/bridge/pkg/utils/stringx"

var systemTopologies = []string{"system-events", "system-email-notifications", "system-cloud-notifications"}

func IsSystemTopology(topologyName string) bool {
	return stringx.InArray(systemTopologies, topologyName)
}
