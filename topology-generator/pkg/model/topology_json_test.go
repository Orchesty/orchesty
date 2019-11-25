package model

import (
	"testing"

	"github.com/stretchr/testify/assert"
	"go.mongodb.org/mongo-driver/bson/primitive"
)

func Test_NodeStringUtils(t *testing.T) {
	t.Run("Test handling names based on Node struct", func(t *testing.T) {
		t.Run("Get service name", getServiceName)
		t.Run("Get next nod not empty", getNextNode)
		t.Run("Get next nod empty", getNextNodeEmpty)
	})
}

func Test_TopologyStringUtils(t *testing.T) {
	t.Run("Test handling names based on Topology struct", func(t *testing.T) {
		t.Run("Get normalized name", getNormalizeName)
		t.Run("Get docker name", getDockerName)
		t.Run("Get multi node name", getMultiNodeName)
		t.Run("Get swarm name", getSwarmName)
		t.Run("Get probe service name", getProbeServiceName)
		t.Run("Get counter service name", getCounterServiceName)
		t.Run("Get config name", getConfigName)
		t.Run("Get topology prefix", getTopologyPrefix)
		t.Run("Get volumes", getVolumes)
	})
}

func getVolumes(t *testing.T) {
	topology := getTestTopology()

	tests := []struct {
		Mode     Adapter
		Source   string
		Topology string
		Result   []string
	}{
		{
			Mode:     ModeCompose,
			Source:   "/tmp/data",
			Topology: "/srv/app/topology.json",
			Result:   []string{"/tmp/data/5cc0474e4e9acc00282bb942-test/topology.json:/srv/app/topology.json"},
		},
		{
			Mode:     ModeSwarm,
			Source:   "/tmp/data",
			Topology: "/srv/app/topology.json",
			Result:   make([]string, 0),
		},
	}

	for _, test := range tests {
		result := topology.GetVolumes(test.Mode, test.Source, test.Topology)

		if assert.EqualValues(t, test.Result, result) != true {
			t.Errorf("bad result: expected `%s`, got `%s`", test.Result, result)
		}
	}
}

func getTopologyPrefix(t *testing.T) {
	topology := getTestTopology()
	expected := "dev_4e9acc00282bb942"
	result := topology.GetTopologyPrefix("dev")

	if result != expected {
		t.Errorf("bad result: expected `%s`, got `%s`", expected, result)
	}
}

func getConfigName(t *testing.T) {
	topology := getTestTopology()
	expected := "dev_4e9acc00282bb942_config"
	result := topology.GetConfigName("dev")

	if result != expected {
		t.Errorf("bad result: expected `%s`, got `%s`", expected, result)
	}
}

func getCounterServiceName(t *testing.T) {
	topology := getTestTopology()
	expected := "5cc0474e4e9acc00282bb942_counter"
	result := topology.GetCounterServiceName()

	if result != expected {
		t.Errorf("bad result: expected `%s`, got `%s`", expected, result)
	}
}

func getProbeServiceName(t *testing.T) {
	topology := getTestTopology()
	expected := "5cc0474e4e9acc00282bb942_probe"
	result := topology.GetProbeServiceName()

	if result != expected {
		t.Errorf("bad result: expected `%s`, got `%s`", expected, result)
	}
}

func getSwarmName(t *testing.T) {
	expected := "dev_4e9acc00282bb942"
	topology := getTestTopology()
	result := topology.GetSwarmName("dev")

	if result != expected {
		t.Errorf("bad result: expected `%s`, got `%s`", expected, result)
	}
}

func getMultiNodeName(t *testing.T) {
	expected := "5cc0474e4e9acc00282bb942_mb"
	topology := getTestTopology()
	result := topology.GetMultiNodeName()

	if result != expected {
		t.Errorf("bad result: expected `%s`, got `%s`", expected, result)
	}
}

func getDockerName(t *testing.T) {
	expected := "5cc0474e4e9acc00282bb942-test"
	topology := getTestTopology()
	result := topology.GetDockerName()

	if result != expected {
		t.Errorf("bad result: expected `%s`, got `%s`", expected, result)
	}
}

func getNormalizeName(t *testing.T) {
	expected := "5cc0474e4e9acc00282bb942-test"
	topology := getTestTopology()
	result := topology.NormalizeName()

	if result != expected {
		t.Errorf("bad result: expected `%s`, got `%s`", expected, result)
	}
}

func getNextNode(t *testing.T) {
	expected := "5cc047dd4e9acc002a200c14-xml"
	node := getTestNode()
	result := node.GetNext()

	if result[0] != expected {
		t.Errorf("bad result: expected `%s`, got `%s`", expected, result[0])
	}
}

func getNextNodeEmpty(t *testing.T) {
	node := getTestNode()
	node.Next = nil
	result := node.GetNext()

	if len(result) != 0 {
		t.Errorf("bad result: expected empty result, got `%s`", result)
	}
}

func getServiceName(t *testing.T) {
	expected := "5cc047dd4e9acc002a200c12-start"
	node := getTestNode()
	result := node.GetServiceName()

	if result != expected {
		t.Errorf("bad result: expected `%s`, got `%s`", expected, result)
	}
}

func getTestTopology() Topology {

	id, _ := primitive.ObjectIDFromHex("5cc0474e4e9acc00282bb942")

	return Topology{
		ID:         id,
		Name:       "test",
		Version:    1,
		Descr:      "main topology test",
		Visibility: "draft",
		Status:     "New",
		Enabled:    false,
		Bpmn:       "",
		RawBpmn:    "",
		Deleted:    false,
	}
}

func getTestNode() Node {

	id, _ := primitive.ObjectIDFromHex("5cc047dd4e9acc002a200c12")
	var next = []NodeNext{
		{
			ID:   "5cc047dd4e9acc002a200c14",
			Name: "Xml_parser",
		},
	}

	return Node{
		ID:       id,
		Name:     "start",
		Topology: "5cc0474e4e9acc00282bb942",
		Next:     next,
		Type:     "start",
		Handler:  "event",
		Enabled:  true,
		Deleted:  false,
	}
}
