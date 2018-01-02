package probe

import (
	"testing"
	"io/ioutil"
	"encoding/json"
	"github.com/stretchr/testify/assert"
)

// TestTopologyJson checks the decoding of topology.json file into internal TopologyJson struct
func TestTopologyJson(t *testing.T) {
	var topology TopologyJson
	data, _ := ioutil.ReadFile("./topology_test.json")
	json.Unmarshal(data, &topology)

	assert.Equal(t, "5a3781cf4a5a0e001e644064-sal-for-cro", topology.ID)
	assert.Equal(t, "5a3781cf4a5a0e001e644064", topology.TopologyId)
	assert.Equal(t, "sales-force-cron", topology.TopologyName)

	assert.Len(t, topology.Bridges, 6)

	for _, bridge := range topology.Bridges {
		assert.NotNil(t, bridge.Debug.Url, "Debug url is empty")
		assert.NotEqual(t, "", bridge.Debug.Url, "Debug url is empty")
		assert.Condition(t, func() (success bool) {
			return bridge.Debug.Port > 0
		}, "Debug port must be greater than 0")
	}
}
