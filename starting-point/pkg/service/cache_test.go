package service

import (
	"fmt"
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

	Cache.FindTopologyByID(customIDOne, customIDOne, "", false)
	Cache.FindTopologyByID(customIDOne, customIDTwo, "", false)
	Cache.FindTopologyByID(customIDOne, customIDTwo, "", false)
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

	Cache.FindTopologyByName(topology, node, "", false)
	Cache.FindTopologyByName(topology, "NodeTwo", "", false)
	Cache.FindTopologyByName(topology, "NodeTwo", "", false)
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

func (m *MongoMock) FindNodeByID(nodeID, topologyID, humanTaskID string, isHumanTask bool) *storage.Node {
	return &storage.Node{
		ID:   customObjectID,
		Name: node,
	}
}

func (m *MongoMock) FindNodeByName(nodeName, topologyID, humanTaskID string, isHumanTask bool) []storage.Node {
	return []storage.Node{{
		ID:   customObjectID,
		Name: node,
	}}
}

func (m *MongoMock) FindTopologyByID(topologyID, nodeID, humanTaskID string, isHumanTask bool) *storage.Topology {
	return &topologyObject
}

func (m *MongoMock) FindTopologyByName(topologyName, nodeName, humanTaskID string, isHumanTask bool) *storage.Topology {
	return &topologyObject
}

func (m *MongoMock) FindTopologyByApplication(topologyName, nodeName, token string) (*storage.Topology, *storage.Webhook) {
	return &topologyObject, &webhookObject
}

func (m *MongoMock) FindHumanTask(nodeID, topologyID, humanTaskID string) *storage.HumanTask {
	return &storage.HumanTask{
		ID:            customObjectID,
		CorrelationID: "correlationID",
		ProcessID:     "processID",
		ContentType:   "contentType",
		SequenceID:    "sequenceID",
		ParentID:      "parentID",
		ParentProcess: "parentProcess",
	}
}
