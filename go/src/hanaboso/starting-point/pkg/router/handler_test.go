package router

import (
	"bytes"
	"github.com/mongodb/mongo-go-driver/bson/objectid"
	"net/http"
	"starting-point/pkg/service"
	"starting-point/pkg/storage"
	"testing"
)

var topology = "Topology"
var node = "Node"
var customID = "4cb174e20000000000000000"
var customObjectID, _ = objectid.FromHex(customID)
var topologyObject = storage.Topology{
	ID:   customObjectID,
	Name: topology,
	Node: &storage.Node{
		ID:   customObjectID,
		Name: node,
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

type CacheMockTopology struct {
	*service.CacheDefault
}

type CacheNoMock struct {
	*service.CacheDefault
}

func mockCache(t int) {
	service.RabbitMq = &RabbitMock{}

	switch t {
	case 1:
		service.Cache = &CacheMock{}
		break
	case 2:
		service.Cache = &CacheMockTopology{}
		break
	case 3:
		service.Cache = &CacheNoMock{}
		break
	}
}

func (r *RabbitMock) SndMessage(request *http.Request, topology storage.Topology, init map[string]float64) {
	return
}

func (c *CacheMock) InvalidateCache(topologyName string) int {
	return 0
}

func (c *CacheMock) FindTopologyByID(topologyID, nodeID string) *storage.Topology {
	return &topologyObject
}

func (c *CacheMock) FindTopologyByName(topologyName, nodeName string) []storage.Topology {
	return []storage.Topology{topologyObject}
}

func TestHandleStatus(t *testing.T) {
	r, _ := http.NewRequest("GET", "/starting-point/status", nil)
	assertResponse(t, r, 200, `{"status":"OK"}`)
}

func TestHandleRunByID(t *testing.T) {
	mockCache(1)

	r, _ := http.NewRequest("POST", "/starting-point/topologies/a/nodes/b/run", bytes.NewReader([]byte("[]")))
	assertResponse(t, r, 200, `{"started":1,"state":"ok"}`)

	mockCache(1)
	r, _ = http.NewRequest("POST", "/starting-point/human-task/topologies/a/nodes/b/run", bytes.NewReader([]byte("[]")))
	assertResponse(t, r, 200, `{"started":1,"state":"ok"}`)

	mockCache(1)
	r, _ = http.NewRequest("POST", "/starting-point/human-task/topologies/a/nodes/b/stop", bytes.NewReader([]byte("[]")))
	assertResponse(t, r, 200, `{"started":1,"state":"ok"}`)
}

func TestHandleRunByName(t *testing.T) {
	mockCache(1)

	r, _ := http.NewRequest("POST", "/starting-point/topologies/a/nodes/b/run-by-name", bytes.NewReader([]byte("[]")))
	assertResponse(t, r, 200, `{"started":1,"state":"ok"}`)

	mockCache(1)
	r, _ = http.NewRequest("POST", "/starting-point/human-task/topologies/a/nodes/b/run-by-name", bytes.NewReader([]byte("[]")))
	assertResponse(t, r, 200, `{"started":1,"state":"ok"}`)

	mockCache(1)
	r, _ = http.NewRequest("POST", "/starting-point/human-task/topologies/a/nodes/b/stop-by-name", bytes.NewReader([]byte("[]")))
	assertResponse(t, r, 200, `{"started":1,"state":"ok"}`)
}

func TestHandleInvalidateCache(t *testing.T) {
	mockCache(1)
	r, _ := http.NewRequest("POST", "/starting-point/topologies/a/invalidate-cache", nil)
	assertResponse(t, r, 200, `{"cache":0}`)
}

// Test case: Find topology but not found Node

func (c *CacheMockTopology) FindTopologyByID(topologyID, nodeID string) *storage.Topology {
	return &topologyNoNodeObject
}

func (c *CacheMockTopology) FindTopologyByName(topologyName, nodeName string) []storage.Topology {
	return []storage.Topology{}
}

func TestHandleRunByIDNodeNotFound(t *testing.T) {
	mockCache(2)

	r, _ := http.NewRequest("POST", "/starting-point/topologies/a/nodes/b/run", bytes.NewReader([]byte("[]")))
	assertResponse(t, r, 404, `{"message":"Node with key 'b' not found!"}`)
}

func TestHandleRunByNameNodeNotFound(t *testing.T) {
	mockCache(2)

	r, _ := http.NewRequest("POST", "/starting-point/topologies/a/nodes/b/run-by-name", bytes.NewReader([]byte("[]")))
	assertResponse(t, r, 404, `{"message":"Topology with name 'a' and node with name 'b' not found!"}`)
}

// Test case: Not find topology and not found Node

func (c *CacheNoMock) FindTopologyByID(topologyID, nodeID string) *storage.Topology {
	return nil
}

func (c *CacheNoMock) FindTopologyByName(topologyName, nodeName string) []storage.Topology {
	return []storage.Topology{}
}

func TestHandleRunByIDTopologyNotFound(t *testing.T) {
	mockCache(3)

	r, _ := http.NewRequest("POST", "/starting-point/topologies/a/nodes/b/run", bytes.NewReader([]byte("[]")))
	assertResponse(t, r, 404, `{"message":"Topology with key 'a' not found!"}`)
}

func TestHandleRunByNameInvalidInput(t *testing.T) {
	mockCache(3)

	r, _ := http.NewRequest("POST", "/starting-point/topologies/a/nodes/b/run-by-name", bytes.NewReader([]byte("invalid")))
	assertResponse(t, r, 400, `{"message":"Content is not valid!"}`)
}
