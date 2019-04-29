package router

import (
	"bytes"
	"go.mongodb.org/mongo-driver/bson/primitive"
	"net/http"
	"starting-point/pkg/service"
	"starting-point/pkg/storage"
	"testing"
)

var topology = "Topology"
var node = "Node"
var customID = "4cb174e20000000000000000"
var customObjectID, _ = primitive.ObjectIDFromHex(customID)
var topologyObject = storage.Topology{
	ID:   customObjectID,
	Name: topology,
	Node: &storage.Node{
		ID:   customObjectID,
		Name: node,
		HumanTask: &storage.HumanTask{
			ID:            customObjectID,
			ParentProcess: "parentProcess",
			ParentID:      "parentID",
			SequenceID:    "sequenceID",
			ContentType:   "contentType",
			ProcessID:     "processID",
			CorrelationID: "correlationID",
		},
	},
}
var topologyNoNodeObject = storage.Topology{
	ID:   customObjectID,
	Name: topology,
	Node: nil,
}

type RabbitMock struct {
	*service.RabbitDefault
}

type CacheMock struct {
	*service.CacheDefault
}

type MongoMock struct {
	*storage.MongoDefault
}

type CacheMockTopology struct {
	*service.CacheDefault
}

type MongoMockTopology struct {
	*storage.MongoDefault
}

type CacheNoMock struct {
	*service.CacheDefault
}

type MongoNoMock struct {
	*storage.MongoDefault
}

type MongoNoMockHumanTask struct {
	*storage.MongoDefault
}

func mockCache(t int) {
	service.RabbitMq = &RabbitMock{}

	switch t {
	case 1:
		service.Cache = &CacheMock{}
		storage.Mongo = &MongoMock{}
		break
	case 2:
		service.Cache = &CacheMockTopology{}
		storage.Mongo = &MongoMockTopology{}
		break
	case 3:
		service.Cache = &CacheNoMock{}
		storage.Mongo = &MongoNoMock{}
		break
	case 4:
		storage.Mongo = &MongoNoMockHumanTask{}
		break
	}
}

func (r *RabbitMock) SndMessage(request *http.Request, topology storage.Topology, init map[string]float64, isHuman bool, isStop bool) {
	return
}

func (c *CacheMock) InvalidateCache(topologyName string) int {
	return 0
}

func (c *CacheMock) FindTopologyByID(topologyID, nodeID, processID string, isHumanTask bool) *storage.Topology {
	return &topologyObject
}

func (c *CacheMock) FindTopologyByName(topologyName, nodeName, processID string, isHumanTask bool) []storage.Topology {
	return []storage.Topology{topologyObject}
}

func (c *MongoMock) FindTopologyByID(topologyID, nodeID, processID string, isHumanTask bool) *storage.Topology {
	return &topologyObject
}

func (c *MongoMock) FindTopologyByName(topologyName, nodeName, processID string, isHumanTask bool) []storage.Topology {
	return []storage.Topology{topologyObject}
}

func TestHandleStatus(t *testing.T) {
	r, _ := http.NewRequest("GET", "/status", nil)
	assertResponse(t, r, 200, `{"status":"OK"}`)
}

func TestHandleRunByID(t *testing.T) {
	mockCache(1)

	r, _ := http.NewRequest("POST", "/topologies/a/nodes/b/run", bytes.NewReader([]byte("[]")))
	assertResponse(t, r, 200, `{"started":1,"state":"ok"}`)

	mockCache(1)
	r, _ = http.NewRequest("POST", "/human-task/topologies/a/nodes/b/run", bytes.NewReader([]byte("[]")))
	assertResponse(t, r, 200, `{"started":1,"state":"ok"}`)

	mockCache(1)
	r, _ = http.NewRequest("POST", "/human-task/topologies/a/nodes/b/stop", bytes.NewReader([]byte("[]")))
	assertResponse(t, r, 200, `{"started":1,"state":"ok"}`)
}

func TestHandleRunByName(t *testing.T) {
	mockCache(1)

	r, _ := http.NewRequest("POST", "/topologies/a/nodes/b/run-by-name", bytes.NewReader([]byte("[]")))
	assertResponse(t, r, 200, `{"started":1,"state":"ok"}`)

	mockCache(1)
	r, _ = http.NewRequest("POST", "/human-task/topologies/a/nodes/b/run-by-name", bytes.NewReader([]byte("[]")))
	assertResponse(t, r, 200, `{"started":1,"state":"ok"}`)

	mockCache(1)
	r, _ = http.NewRequest("POST", "/human-task/topologies/a/nodes/b/stop-by-name", bytes.NewReader([]byte("[]")))
	assertResponse(t, r, 200, `{"started":1,"state":"ok"}`)
}

func TestHandleInvalidateCache(t *testing.T) {
	mockCache(1)
	r, _ := http.NewRequest("POST", "/topologies/a/invalidate-cache", nil)
	assertResponse(t, r, 200, `{"cache":0}`)
}

// Test case: Find topology but not found Node

func (c *CacheMockTopology) FindTopologyByID(topologyID, nodeID, processID string, isHumanTask bool) *storage.Topology {
	return &topologyNoNodeObject
}

func (c *CacheMockTopology) FindTopologyByName(topologyName, nodeName, processID string, isHumanTask bool) []storage.Topology {
	return []storage.Topology{}
}

func (c *MongoMockTopology) FindTopologyByID(topologyID, nodeID, processID string, isHumanTask bool) *storage.Topology {
	return &topologyNoNodeObject
}

func (c *MongoMockTopology) FindTopologyByName(topologyName, nodeName, processID string, isHumanTask bool) []storage.Topology {
	return []storage.Topology{}
}

func TestHandleRunByIDNodeNotFound(t *testing.T) {
	mockCache(2)

	r, _ := http.NewRequest("POST", "/topologies/a/nodes/b/run", bytes.NewReader([]byte("[]")))
	assertResponse(t, r, 404, `{"message":"Node with key 'b' not found!"}`)
}

func TestHandleRunByNameNodeNotFound(t *testing.T) {
	mockCache(2)

	r, _ := http.NewRequest("POST", "/topologies/a/nodes/b/run-by-name", bytes.NewReader([]byte("[]")))
	assertResponse(t, r, 404, `{"message":"Topology with name 'a' and node with name 'b' not found!"}`)
}

// Test case: Not find topology and not found Node

func (c *CacheNoMock) FindTopologyByID(topologyID, nodeID, processID string, isHumanTask bool) *storage.Topology {
	return nil
}

func (c *CacheNoMock) FindTopologyByName(topologyName, nodeName, processID string, isHumanTask bool) []storage.Topology {
	return []storage.Topology{}
}

func (c *MongoNoMock) FindTopologyByID(topologyID, nodeID, processID string, isHumanTask bool) *storage.Topology {
	return nil
}

func (c *MongoNoMock) FindTopologyByName(topologyName, nodeName, processID string, isHumanTask bool) []storage.Topology {
	return []storage.Topology{}
}

func TestHandleRunByIDTopologyNotFound(t *testing.T) {
	mockCache(3)

	r, _ := http.NewRequest("POST", "/topologies/a/nodes/b/run", bytes.NewReader([]byte("[]")))
	assertResponse(t, r, 404, `{"message":"Topology with key 'a' not found!"}`)
}

func TestHandleRunByNameInvalidInput(t *testing.T) {
	mockCache(3)

	r, _ := http.NewRequest("POST", "/topologies/a/nodes/b/run-by-name", bytes.NewReader([]byte("invalid")))
	assertResponse(t, r, 400, `{"message":"Content is not valid!"}`)
}

// Test case: Find topology and node but not human task

func (c *MongoNoMockHumanTask) FindTopologyByID(topologyID, nodeID, processID string, isHumanTask bool) *storage.Topology {
	topology := topologyObject
	topology.Node.HumanTask = nil

	return &topology
}

func (c *MongoNoMockHumanTask) FindTopologyByName(topologyName, nodeName, processID string, isHumanTask bool) []storage.Topology {
	return []storage.Topology{}
}

func TestHandleRunByIDHumanTaskNotFound(t *testing.T) {
	mockCache(4)

	r, _ := http.NewRequest("POST", "/human-task/topologies/a/nodes/b/token/c/run", bytes.NewReader([]byte("[]")))
	assertResponse(t, r, 404, `{"message":"HumanTask with token 'c' not found!"}`)
}

func TestHandleRunByNameHumanTaskNotFound(t *testing.T) {
	mockCache(4)

	r, _ := http.NewRequest("POST", "/human-task/topologies/a/nodes/b/token/c/run-by-name", bytes.NewReader([]byte("[]")))
	assertResponse(t, r, 404, `{"message":"Topology with name 'a', node with name 'b' and human task with token 'c' not found!"}`)
}
