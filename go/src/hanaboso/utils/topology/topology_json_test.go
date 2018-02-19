package topology

import (
	"testing"
	"io/ioutil"
	"encoding/json"
	"github.com/stretchr/testify/assert"
	"strings"
)

// TestTopologyJson checks the decoding of topology.json file into internal TopologyJson struct
func TestTopologyJson(t *testing.T) {
	var topology TopologyJson
	data, err := ioutil.ReadFile("./topology_json_test.json")
	assert.Nil(t, err, "Couldn't load test topology json file.")

	json.Unmarshal(data, &topology)

	assert.Equal(t, "5a3781cf4a5a0e001e644064-sal-for-cro", topology.ID)
	assert.Equal(t, "5a3781cf4a5a0e001e644064", topology.TopologyId)
	assert.Equal(t, "sales-force-cron", topology.TopologyName)

	assert.Len(t, topology.Bridges, 6)

	for _, bridge := range topology.Bridges {
		assert.NotNil(t, bridge.ID)
		assert.NotEqual(t, "", bridge.ID)

		checkLabel(t, bridge.Label)
		assert.Equal(t, bridge.ID, bridge.Label.ID)

		checkDebug(t, bridge.Debug)
		checkNext(t, bridge.Next)
		checkWorker(t, bridge.Worker)
	}
}

// checkLabel checks the validity of bridge.label section
func checkLabel(t *testing.T, label TopologyBridgeLabelJson) {
	assert.NotNil(t, label.ID)
	assert.NotEqual(t, "", label.ID)

	assert.NotNil(t, label.NodeId)
	assert.NotEqual(t, "", label.NodeId)

	assert.NotNil(t, label.NodeName)
	assert.NotEqual(t, "", label.NodeName)
}

// checkDebug checks the validity of bridge.debug section
func checkDebug(t *testing.T, debug TopologyBridgeDebugJson) {
	assert.NotNil(t, debug.Url, "Debug url is empty")
	assert.NotEqual(t, "", debug.Url, "Debug url is empty")
	assert.Condition(t, func() (success bool) {
		return debug.Port > 0
	}, "Debug port must be greater than 0")
}

// checkNext checks if next array is either empty or contains non-empty array of string
func checkNext(t *testing.T, next []string) {
	if len(next) < 1 {
		return
	}

	for _, nextId := range next {
		assert.NotNil(t, nextId, "Next must not be nil")
		assert.NotEqual(t, "", nextId, "Next must not be empty")
	}
}

// checkWorker checks if worker contains type and settings properties
func checkWorker(t *testing.T, worker TopologyBridgeWorkerJson) {
	assert.NotNil(t, worker.Type)
	assert.NotEqual(t, "", worker.Type)
	assert.Condition(t, func() (success bool) {
		if strings.Contains(worker.Type, "worker.") {
			return true
		}
		if strings.Contains(worker.Type, "splitter.") {
			return true
		}

		return false
	})
}
