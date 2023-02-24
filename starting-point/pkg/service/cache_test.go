package service

import (
	"fmt"
	"starting-point/pkg/enum"
	"testing"

	"github.com/stretchr/testify/assert"
	"go.mongodb.org/mongo-driver/bson/primitive"
	"starting-point/pkg/storage"
)

var topology = "Topology"
var node = "Node"
var token = "Token"
var customIDOne = "4cb174e20000000000000000"
var customIDTwo = "4cb174e20000000000000001"
var customObjectID, _ = primitive.ObjectIDFromHex(customIDOne)
var topologyObject = storage.Topology{
	ID:   customObjectID,
	Name: topology,
	Node: &storage.Node{
		ID:   customObjectID,
		Name: node,
	},
}
var webhookObject = storage.Webhook{
	ID:          customObjectID,
	User:        "User",
	Token:       "Token",
	Node:        "Node",
	Topology:    "Topology",
	Application: "Application",
}

type MongoMock struct {
	*storage.MongoDefault
}

func TestCache(t *testing.T) {
	CreateCache()
	Cache = &CacheDefault{mongo: &MongoMock{}}
	Cache.InitCache()

	Cache.FindTopologyByID(customIDOne, customIDOne, false, enum.NodeType_StartEvents)
	Cache.FindTopologyByID(customIDOne, customIDTwo, false, enum.NodeType_StartEvents)
	Cache.FindTopologyByID(customIDOne, customIDTwo, false, enum.NodeType_StartEvents)
	cache, _ := Cache.GetCache().Get(topology)
	cacheCache, _ := Cache.GetCache().Get(cache.([]string)[0])

	assert.Equal(t, 3, Cache.GetCache().ItemCount())
	assert.Equal(t, []string{
		fmt.Sprintf("%s-%s", customIDOne, customIDOne),
		fmt.Sprintf("%s-%s", customIDOne, customIDTwo),
	}, cache)
	assert.Equal(t, &topologyObject, cacheCache)

	Cache.InvalidateCache("Unknown")
	assert.Equal(t, 3, Cache.GetCache().ItemCount())
	Cache.InvalidateCache(topology)
	assert.Equal(t, 0, Cache.GetCache().ItemCount())

	Cache.FindTopologyByName(topology, node)
	Cache.FindTopologyByName(topology, "NodeTwo")
	Cache.FindTopologyByName(topology, "NodeTwo")
	cache, _ = Cache.GetCache().Get(topology)
	cacheCache, _ = Cache.GetCache().Get(cache.([]string)[0])
	assert.Equal(t, 3, Cache.GetCache().ItemCount())
	assert.Equal(t, []string{
		fmt.Sprintf("%s-%s", topology, node),
		fmt.Sprintf("%s-%s", topology, "NodeTwo"),
	}, cache)
	assert.Equal(t, &topologyObject, cacheCache)
	Cache.InvalidateCache(topology)
	assert.Equal(t, 0, Cache.GetCache().ItemCount())

	Cache.FindTopologyByApplication(topology, node, token)
	Cache.FindTopologyByApplication(topology, node, token)
	Cache.FindTopologyByApplication(topology, node, "TokenTwo")
	cache, _ = Cache.GetCache().Get(topology)
	cacheCache, _ = Cache.GetCache().Get(cache.([]string)[0])
	assert.Equal(t, 3, Cache.GetCache().ItemCount())
	assert.Equal(t, []string{
		fmt.Sprintf("%s-%s-%s", topology, node, token),
		fmt.Sprintf("%s-%s-%s", topology, node, "TokenTwo"),
	}, cache)
	assert.Equal(t, map[string]interface{}{
		"topology": &topologyObject,
		"webhook":  &webhookObject,
	}, cacheCache)
}

func (m *MongoMock) FindNodeByID(nodeID, topologyID string, uiRun bool, allowedTypes []string) *storage.Node {
	return &storage.Node{
		ID:   customObjectID,
		Name: node,
	}
}

func (m *MongoMock) FindNodeByName(nodeName, topologyID string) []storage.Node {
	return []storage.Node{{
		ID:   customObjectID,
		Name: node,
	}}
}

func (m *MongoMock) FindTopologyByID(topologyID, nodeID string, uiRun bool, allowedTypes []string) *storage.Topology {
	return &topologyObject
}

func (m *MongoMock) FindTopologyByName(topologyName, nodeName string) *storage.Topology {
	return &topologyObject
}

func (m *MongoMock) FindTopologyByApplication(topologyName, nodeName, token string) (*storage.Topology, *storage.Webhook) {
	return &topologyObject, &webhookObject
}
