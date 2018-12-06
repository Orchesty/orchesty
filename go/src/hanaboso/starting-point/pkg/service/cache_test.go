package service

import (
	"fmt"
	"github.com/mongodb/mongo-go-driver/bson/objectid"
	"github.com/stretchr/testify/assert"
	"starting-point/pkg/storage"
	"testing"
)

var topology = "Topology"
var node = "Node"
var customIDOne = "4cb174e20000000000000000"
var customIDTwo = "4cb174e20000000000000001"
var customObjectID, _ = objectid.FromHex(customIDOne)
var topologyObject = storage.Topology{
	ID:   customObjectID,
	Name: topology,
	Node: &storage.Node{
		ID:   customObjectID,
		Name: node,
	},
}

type MongoMock struct {
	*storage.MongoDefault
}

func TestCache(t *testing.T) {
	CreateCache()
	Cache = &CacheDefault{mongo: &MongoMock{}}
	Cache.InitCache()

	Cache.FindTopologyByID(customIDOne, customIDOne)
	Cache.FindTopologyByID(customIDOne, customIDTwo)
	Cache.FindTopologyByID(customIDOne, customIDTwo)
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
	assert.Equal(t, []storage.Topology{topologyObject}, cacheCache)
}

func (m *MongoMock) FindNodeByID(nodeID, topologyID string) *storage.Node {
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

func (m *MongoMock) FindTopologyByID(topologyID, nodeID string) *storage.Topology {
	return &topologyObject
}

func (m *MongoMock) FindTopologyByName(topologyName, nodeName string) []storage.Topology {
	return []storage.Topology{topologyObject}
}
