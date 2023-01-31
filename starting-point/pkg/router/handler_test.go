package router

import (
	"bytes"
	"net/http"
	"testing"

	"go.mongodb.org/mongo-driver/bson/primitive"
	"starting-point/pkg/service"
	"starting-point/pkg/storage"
	"starting-point/pkg/utils"
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
	},
}
var topologyNoNodeObject = storage.Topology{
	ID:   customObjectID,
	Name: topology,
	Node: nil,
}
var webhookObject = storage.Webhook{
	ID:          customObjectID,
	User:        "User",
	Token:       "Token",
	Node:        "Node",
	Topology:    "Topology",
	Application: "Application",
}

type RabbitMock struct {
	service.RabbitSvc
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

type MongoMockConnected struct {
	*storage.MongoDefault
}

type CacheNoMock struct {
	*service.CacheDefault
}

type MongoNoMock struct {
	*storage.MongoDefault
}

func mockCache(t int) {
	service.RabbitMq = RabbitMock{}

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

func prepareMongo() {
	storage.CreateMongo()
	_ = storage.Mongo.DropApiTokenCollection()
	_ = storage.Mongo.InsertApiToken("orchesty", []string{"topology:run"}, "")
}

func (r RabbitMock) SendMessage(request *http.Request, topology storage.Topology, init map[string]float64) {
	return
}

func (c *CacheMock) InvalidateCache(topologyName string) int {
	return 0
}

func (c *CacheMock) FindTopologyByID(topologyID, nodeID string, uiRun bool, allowedTypes []string) *storage.Topology {
	return &topologyObject
}

func (c *CacheMock) FindTopologyByName(topologyName, nodeName string) *storage.Topology {
	return &topologyObject
}

func (c *CacheMock) FindTopologyByApplication(topologyName, nodeName, token string) (*storage.Topology, *storage.Webhook) {
	return &topologyObject, &webhookObject
}

func (c *MongoMock) FindTopologyByID(topologyID, nodeID string, uiRun bool, allowedTypes []string) *storage.Topology {
	return &topologyObject
}

func (c *MongoMock) FindTopologyByName(topologyName, nodeName string) *storage.Topology {
	return &topologyObject
}

func (c *MongoMock) FindTopologyByApplication(topologyName, nodeName, token string) (*storage.Topology, *storage.Webhook) {
	return &topologyObject, &webhookObject
}

func (c *MongoMockConnected) IsConnected() bool {
	return true
}

func (r RabbitMock) IsMetricsConnected() bool {
	return true
}

func TestHandleStatus(t *testing.T) {
	storage.Mongo = &MongoMockConnected{}
	service.RabbitMq = RabbitMock{}

	r, _ := http.NewRequest("GET", "/status", nil)
	assertResponse(t, r, 200, `{"database":true,"metrics":true}`)
}

func TestHandleRunByID(t *testing.T) {
	mockCache(1)
	prepareMongo()

	r, _ := http.NewRequest("POST", "/topologies/a/nodes/b/run", bytes.NewReader([]byte("[]")))
	assertResponse(t, r, 200, `{"started":1,"state":"ok"}`)
}

func TestHandleRunByIDUser(t *testing.T) {
	mockCache(1)
	prepareMongo()

	r, _ := http.NewRequest("POST", "/topologies/a/nodes/b/user/c/run", bytes.NewReader([]byte("[]")))
	assertResponse(t, r, 200, `{"started":1,"state":"ok"}`)

	mockCache(1)

	r, _ = http.NewRequest("POST", "/topologies/a/nodes/b/run", bytes.NewReader([]byte(`{"user":"c"}`)))
	assertResponse(t, r, 200, `{"started":1,"state":"ok"}`)

	mockCache(1)

	r, _ = http.NewRequest("POST", "/topologies/a/nodes/b/run", bytes.NewReader([]byte("[]")))
	r.Header.Add(utils.UserID, "c")
	assertResponse(t, r, 200, `{"started":1,"state":"ok"}`)
}

func TestHandleRunByName(t *testing.T) {
	mockCache(1)
	prepareMongo()

	r, _ := http.NewRequest("POST", "/topologies/a/nodes/b/run-by-name", bytes.NewReader([]byte("[]")))
	assertResponse(t, r, 200, `{"started":1,"state":"ok"}`)
}

func TestHandleRunByNameUser(t *testing.T) {
	mockCache(1)
	prepareMongo()
	r, _ := http.NewRequest("POST", "/topologies/a/nodes/b/user/c/run-by-name", bytes.NewReader([]byte("[]")))
	assertResponse(t, r, 200, `{"started":1,"state":"ok"}`)

	mockCache(1)
	r, _ = http.NewRequest("POST", "/topologies/a/nodes/b/run-by-name", bytes.NewReader([]byte(`{"user":"c"}`)))
	assertResponse(t, r, 200, `{"started":1,"state":"ok"}`)

	mockCache(1)
	r, _ = http.NewRequest("POST", "/topologies/a/nodes/b/run-by-name", bytes.NewReader([]byte("[]")))
	r.Header.Add(utils.UserID, "c")
	assertResponse(t, r, 200, `{"started":1,"state":"ok"}`)
}

func TestHandleRunByApplication(t *testing.T) {
	mockCache(1)

	r, _ := http.NewRequest("POST", "/topologies/a/nodes/b/token/c/run", bytes.NewReader([]byte("[]")))
	assertResponse(t, r, 200, `{"started":1,"state":"ok"}`)
}

func TestHandleRunByApplicationOptions(t *testing.T) {
	mockCache(1)

	r, _ := http.NewRequest("OPTIONS", "/topologies/a/nodes/b/token/c/run", bytes.NewReader([]byte("[]")))
	assertResponseWithHeaders(t, r, 204, "", map[string]string{
		"Access-Control-Allow-Origin":      "",
		"Access-Control-Allow-Methods":     "GET, POST, PUT, DELETE, OPTIONS",
		"Access-Control-Allow-Headers":     "Content-Type",
		"Access-Control-Allow-Credentials": "true",
		"Access-Control-Max-Age":           "3600",
	})
}

func TestHandleInvalidateCache(t *testing.T) {
	mockCache(1)
	r, _ := http.NewRequest("POST", "/topologies/a/invalidate-cache", nil)
	assertResponse(t, r, 200, `{"cache":0}`)
}

// Test case: Find topology but not found Node

func (c *CacheMockTopology) FindTopologyByID(topologyID, nodeID string, uiRun bool, allowedTypes []string) *storage.Topology {
	return &topologyNoNodeObject
}

func (c *CacheMockTopology) FindTopologyByName(topologyName, nodeName string) *storage.Topology {
	return nil
}

func (c *CacheMockTopology) FindTopologyByApplication(topologyName, nodeName, token string) (*storage.Topology, *storage.Webhook) {
	return nil, nil
}

func (c *MongoMockTopology) FindTopologyByID(topologyID, nodeID string, uiRun bool, allowedTypes []string) *storage.Topology {
	return &topologyNoNodeObject
}

func (c *MongoMockTopology) FindTopologyByName(topologyName, nodeName string) *storage.Topology {
	return nil
}

func (c *MongoMockTopology) FindTopologyByApplication(topologyName, nodeName, token string) (*storage.Topology, *storage.Webhook) {
	return nil, nil
}

func TestHandleRunByIDNodeNotFound(t *testing.T) {
	mockCache(2)
	prepareMongo()

	r, _ := http.NewRequest("POST", "/topologies/a/nodes/b/run", bytes.NewReader([]byte("[]")))
	assertResponse(t, r, 404, `{"message":"Node with key 'b' not found!"}`)
}

func TestHandleRunByNameNodeNotFound(t *testing.T) {
	mockCache(2)
	prepareMongo()

	r, _ := http.NewRequest("POST", "/topologies/a/nodes/b/run-by-name", bytes.NewReader([]byte("[]")))
	assertResponse(t, r, 404, `{"message":"Topology with name 'a' and node with name 'b' not found!"}`)
}

func TestHandleRunByApplicationNodeNotFound(t *testing.T) {
	mockCache(2)

	r, _ := http.NewRequest("POST", "/topologies/a/nodes/b/token/c/run", bytes.NewReader([]byte("[]")))
	assertResponse(t, r, 404, `{"message":"Topology with name 'a', node with name 'b' and webhook with token 'c' not found!"}`)
}

// Test case: Not find topology and not found Node

func (c *CacheNoMock) FindTopologyByID(topologyID, nodeID string, uiRun bool, allowedTypes []string) *storage.Topology {
	return nil
}

func (c *CacheNoMock) FindTopologyByName(topologyName, nodeName string) *storage.Topology {
	return nil
}

func (c *MongoNoMock) FindTopologyByID(topologyID, nodeID string, uiRun bool, allowedTypes []string) *storage.Topology {
	return nil
}

func (c *MongoNoMock) FindTopologyByName(topologyName, nodeName string) *storage.Topology {
	return nil
}

func TestHandleRunByIDTopologyNotFound(t *testing.T) {
	mockCache(3)
	prepareMongo()

	r, _ := http.NewRequest("POST", "/topologies/a/nodes/b/run", bytes.NewReader([]byte("[]")))
	assertResponse(t, r, 404, `{"message":"Topology with key 'a' not found!"}`)
}

func TestHandleRunByNameInvalidInput(t *testing.T) {
	mockCache(3)
	prepareMongo()

	r, _ := http.NewRequest("POST", "/topologies/a/nodes/b/run-by-name", bytes.NewReader([]byte("invalid")))
	assertResponse(t, r, 400, `{"message":"Content is not valid!"}`)
}

func TestHandleRunByApplicationInvalidInput(t *testing.T) {
	mockCache(3)
	prepareMongo()

	r, _ := http.NewRequest("POST", "/topologies/a/nodes/b/token/c/run", bytes.NewReader([]byte("invalid")))
	assertResponse(t, r, 400, `{"message":"Content is not valid!"}`)
}

// Test case: Find topology and node
