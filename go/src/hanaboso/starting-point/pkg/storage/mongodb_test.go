package storage

import (
	"github.com/mongodb/mongo-go-driver/bson"
	"github.com/mongodb/mongo-go-driver/bson/objectid"
	"github.com/mongodb/mongo-go-driver/mongo"
	"github.com/stretchr/testify/assert"
	"starting-point/pkg/config"
	"testing"
)

var topologyCollection = config.Config.MongoDB.TopologyColl
var nodeCollection = config.Config.MongoDB.NodeColl

var topologySuccessNodeSuccess = "topologySuccessNodeSuccess"
var topologyVisibilityNodeSuccess = "topologyVisibilityNodeSuccess"
var topologyEnabledNodeSuccess = "topologyEnabledNodeSuccess"
var topologyDeletedNodeSuccess = "topologyDeletedNodeSuccess"
var topologySuccessNodeEnabled = "topologySuccessNodeEnabled"
var topologySuccessNodeDeleted = "topologySuccessNodeDeleted"

func TestMongo(t *testing.T) {
	CreateMongo()
	defer func() {
		_ = Mongo.Disconnect()
	}()
	data := prepareData(Mongo.(*MongoDefault).mongo)

	topology := Mongo.FindTopologyByID(data[topologySuccessNodeSuccess][0], data[topologySuccessNodeSuccess][1])
	assert.Equal(t, data[topologySuccessNodeSuccess][0], topology.ID.Hex())
	assert.Equal(t, topologyCollection, topology.Name)
	assert.Equal(t, data[topologySuccessNodeSuccess][1], topology.Node.ID.Hex())
	assert.Equal(t, nodeCollection, topology.Node.Name)

	topology = Mongo.FindTopologyByID(data[topologyVisibilityNodeSuccess][0], data[topologyVisibilityNodeSuccess][1])
	assert.Nil(t, topology)

	topology = Mongo.FindTopologyByID(data[topologyEnabledNodeSuccess][0], data[topologyEnabledNodeSuccess][1])
	assert.Nil(t, topology)

	topology = Mongo.FindTopologyByID(data[topologyDeletedNodeSuccess][0], data[topologyDeletedNodeSuccess][1])
	assert.Nil(t, topology)

	topology = Mongo.FindTopologyByID(data[topologySuccessNodeEnabled][0], data[topologySuccessNodeEnabled][1])
	assert.Nil(t, topology)

	topology = Mongo.FindTopologyByID(data[topologySuccessNodeDeleted][0], data[topologySuccessNodeDeleted][1])
	assert.Nil(t, topology)

	topology = Mongo.FindTopologyByID("Unknown", data[topologySuccessNodeDeleted][1])
	assert.Nil(t, topology)

	topology = Mongo.FindTopologyByID(data[topologySuccessNodeSuccess][0], "Unknown")
	assert.Equal(t, data[topologySuccessNodeSuccess][0], topology.ID.Hex())
	assert.Equal(t, topologyCollection, topology.Name)
	assert.Nil(t, topology.Node)

	topology = Mongo.FindTopologyByID("4cb174e20000000000000000", data[topologySuccessNodeSuccess][1])
	assert.Nil(t, topology)

	topology = Mongo.FindTopologyByID(data[topologySuccessNodeSuccess][0], "4cb174e20000000000000000")
	assert.Equal(t, data[topologySuccessNodeSuccess][0], topology.ID.Hex())
	assert.Equal(t, topologyCollection, topology.Name)
	assert.Nil(t, topology.Node)

	topologies := Mongo.FindTopologyByName(topologyCollection, nodeCollection)
	assert.Equal(t, 2, len(topologies))

	topologies = Mongo.FindTopologyByName("Unknown", nodeCollection)
	assert.Equal(t, 0, len(topologies))

	topologies = Mongo.FindTopologyByName(topologyCollection, "Unknown")
	assert.Equal(t, 0, len(topologies))
}

func prepareData(mongo *mongo.Database) map[string][]string {
	_ = mongo.Collection(topologyCollection).Drop(nil)
	_ = mongo.Collection(nodeCollection).Drop(nil)
	result := make(map[string][]string)

	innerResult, _ := mongo.Collection(topologyCollection).InsertOne(nil, bson.M{
		"name":       topologyCollection,
		"visibility": "public",
		"enabled":    true,
		"deleted":    false,
	})
	topologyID := innerResult.InsertedID.(objectid.ObjectID).Hex()
	innerResult, _ = mongo.Collection(nodeCollection).InsertOne(nil, bson.M{
		"name":     nodeCollection,
		"topology": topologyID,
		"enabled":  true,
		"deleted":  false,
	})
	result[topologySuccessNodeSuccess] = []string{topologyID, innerResult.InsertedID.(objectid.ObjectID).Hex()}

	innerResult, _ = mongo.Collection(topologyCollection).InsertOne(nil, bson.M{
		"name":       topologyCollection,
		"visibility": "public",
		"enabled":    true,
		"deleted":    false,
	})
	topologyID = innerResult.InsertedID.(objectid.ObjectID).Hex()
	innerResult, _ = mongo.Collection(nodeCollection).InsertOne(nil, bson.M{
		"name":     nodeCollection,
		"topology": topologyID,
		"enabled":  true,
		"deleted":  false,
	})
	result[topologySuccessNodeSuccess] = []string{topologyID, innerResult.InsertedID.(objectid.ObjectID).Hex()}

	innerResult, _ = mongo.Collection(topologyCollection).InsertOne(nil, bson.M{
		"name":       topologyCollection,
		"visibility": "private",
		"enabled":    true,
		"deleted":    false,
	})
	topologyID = innerResult.InsertedID.(objectid.ObjectID).Hex()
	innerResult, _ = mongo.Collection(nodeCollection).InsertOne(nil, bson.M{
		"name":     nodeCollection,
		"topology": topologyID,
		"enabled":  true,
		"deleted":  false,
	})
	result[topologyVisibilityNodeSuccess] = []string{topologyID, innerResult.InsertedID.(objectid.ObjectID).Hex()}

	innerResult, _ = mongo.Collection(topologyCollection).InsertOne(nil, bson.M{
		"name":       topologyCollection,
		"visibility": "public",
		"enabled":    false,
		"deleted":    false,
	})
	topologyID = innerResult.InsertedID.(objectid.ObjectID).Hex()
	innerResult, _ = mongo.Collection(nodeCollection).InsertOne(nil, bson.M{
		"name":     nodeCollection,
		"topology": topologyID,
		"enabled":  true,
		"deleted":  false,
	})
	result[topologyEnabledNodeSuccess] = []string{topologyID, innerResult.InsertedID.(objectid.ObjectID).Hex()}

	innerResult, _ = mongo.Collection(topologyCollection).InsertOne(nil, bson.M{
		"name":       topologyCollection,
		"visibility": "public",
		"enabled":    true,
		"deleted":    true,
	})
	topologyID = innerResult.InsertedID.(objectid.ObjectID).Hex()
	innerResult, _ = mongo.Collection(nodeCollection).InsertOne(nil, bson.M{
		"name":     nodeCollection,
		"topology": topologyID,
		"enabled":  true,
		"deleted":  false,
	})
	result[topologyDeletedNodeSuccess] = []string{topologyID, innerResult.InsertedID.(objectid.ObjectID).Hex()}

	innerResult, _ = mongo.Collection(topologyCollection).InsertOne(nil, bson.M{
		"name":       topologyCollection,
		"visibility": "public",
		"enabled":    false,
		"deleted":    false,
	})
	topologyID = innerResult.InsertedID.(objectid.ObjectID).Hex()
	innerResult, _ = mongo.Collection(nodeCollection).InsertOne(nil, bson.M{
		"name":     nodeCollection,
		"topology": topologyID,
		"enabled":  false,
		"deleted":  false,
	})
	result[topologySuccessNodeEnabled] = []string{topologyID, innerResult.InsertedID.(objectid.ObjectID).Hex()}

	innerResult, _ = mongo.Collection(topologyCollection).InsertOne(nil, bson.M{
		"name":       topologyCollection,
		"visibility": "public",
		"enabled":    false,
		"deleted":    false,
	})
	topologyID = innerResult.InsertedID.(objectid.ObjectID).Hex()
	innerResult, _ = mongo.Collection(nodeCollection).InsertOne(nil, bson.M{
		"name":     nodeCollection,
		"topology": topologyID,
		"enabled":  true,
		"deleted":  true,
	})
	result[topologySuccessNodeDeleted] = []string{topologyID, innerResult.InsertedID.(objectid.ObjectID).Hex()}

	return result
}
