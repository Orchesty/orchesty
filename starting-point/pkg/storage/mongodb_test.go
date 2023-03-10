package storage

import (
	"starting-point/pkg/enum"
	"testing"

	"github.com/stretchr/testify/assert"
	"go.mongodb.org/mongo-driver/bson"
	"go.mongodb.org/mongo-driver/bson/primitive"
	"go.mongodb.org/mongo-driver/mongo"
	"starting-point/pkg/config"
)

var nodeCollection = "Node"
var topologyCollection = "Topology"

var topologySuccessNodeSuccess = "topologySuccessNodeSuccess"
var topologyWebhook = "topologyWebhook"
var topologyVisibilityNodeSuccess = "topologyVisibilityNodeSuccess"
var topologyEnabledNodeSuccess = "topologyEnabledNodeSuccess"
var topologyDeletedNodeSuccess = "topologyDeletedNodeSuccess"
var topologySuccessNodeEnabled = "topologySuccessNodeEnabled"
var topologySuccessNodeDeleted = "topologySuccessNodeDeleted"
var topologySuccessWebhookSuccess = "topologySuccessWebhookSuccess"

func TestMongo(t *testing.T) {
	CreateMongo()
	defer Mongo.Disconnect()
	data := prepareData(Mongo.(*MongoDefault).connection.Database)
	allowedTypes := []string{enum.NodeType_Start, enum.NodeType_Cron}

	assert.True(t, Mongo.IsConnected())

	topology := Mongo.FindTopologyByID(data[topologyWebhook][0], data[topologyWebhook][1], true, append(allowedTypes, enum.NodeType_Webhook))
	assert.NotNil(t, topology)
	assert.NotNil(t, topology.Node)

	topology = Mongo.FindTopologyByID(data[topologyWebhook][0], data[topologyWebhook][1], false, allowedTypes)
	assert.NotNil(t, topology)
	assert.Nil(t, topology.Node)

	topology = Mongo.FindTopologyByID(data[topologyVisibilityNodeSuccess][0], data[topologyVisibilityNodeSuccess][1], false, allowedTypes)
	assert.Nil(t, topology)

	topology = Mongo.FindTopologyByID(data[topologyEnabledNodeSuccess][0], data[topologyEnabledNodeSuccess][1], false, allowedTypes)
	assert.Nil(t, topology)

	topology = Mongo.FindTopologyByID(data[topologyDeletedNodeSuccess][0], data[topologyDeletedNodeSuccess][1], false, allowedTypes)
	assert.Nil(t, topology)

	topology = Mongo.FindTopologyByID(data[topologySuccessNodeEnabled][0], data[topologySuccessNodeEnabled][1], false, allowedTypes)
	assert.Nil(t, topology)

	topology = Mongo.FindTopologyByID(data[topologySuccessNodeDeleted][0], data[topologySuccessNodeDeleted][1], false, allowedTypes)
	assert.Nil(t, topology)

	topology = Mongo.FindTopologyByID("Unknown", data[topologySuccessNodeDeleted][1], false, allowedTypes)
	assert.Nil(t, topology)

	topology = Mongo.FindTopologyByID(data[topologySuccessNodeSuccess][0], "Unknown", false, allowedTypes)
	assert.Equal(t, data[topologySuccessNodeSuccess][0], topology.ID.Hex())
	assert.Equal(t, topologyCollection, topology.Name)
	assert.Nil(t, topology.Node)

	topology = Mongo.FindTopologyByID("4cb174e20000000000000000", data[topologySuccessNodeSuccess][1], false, allowedTypes)
	assert.Nil(t, topology)

	topology = Mongo.FindTopologyByID(data[topologySuccessNodeSuccess][0], "4cb174e20000000000000000", false, allowedTypes)
	assert.Equal(t, data[topologySuccessNodeSuccess][0], topology.ID.Hex())
	assert.Equal(t, topologyCollection, topology.Name)
	assert.Nil(t, topology.Node)

	topology = Mongo.FindTopologyByName(topologyCollection, nodeCollection)
	assert.NotNil(t, topology)
	assert.Equal(t, int32(8), topology.Version)

	topology = Mongo.FindTopologyByName("Unknown", nodeCollection)
	assert.Nil(t, topology)

	topology = Mongo.FindTopologyByName(topologyCollection, "Unknown")
	assert.Nil(t, topology)

	topology, webhook := Mongo.FindTopologyByApplication(topologyCollection, nodeCollection, "Token")
	id, _ := primitive.ObjectIDFromHex(data[topologySuccessWebhookSuccess][2])
	assert.NotNil(t, topology)
	assert.Equal(t, &Webhook{
		ID:          id,
		User:        "User",
		Token:       "Token",
		Node:        "Node",
		Topology:    "Topology",
		Application: "Application",
	}, webhook)

	topology, webhook = Mongo.FindTopologyByApplication(topologyCollection, nodeCollection, "Unknown")
	assert.Nil(t, topology)
	assert.Nil(t, webhook)

	assert.NotNil(t, Mongo.FindTopologyByName(topologyCollection, nodeCollection))
}

func prepareData(mongo *mongo.Database) map[string][]string {
	_ = mongo.Collection(config.MongoDB.NodeColl).Drop(nil)
	_ = mongo.Collection(config.MongoDB.TopologyColl).Drop(nil)
	_ = mongo.Collection(config.MongoDB.WebhookColl).Drop(nil)
	result := make(map[string][]string)

	innerResult, _ := mongo.Collection(config.MongoDB.TopologyColl).InsertOne(nil, bson.M{
		"name":       topologyCollection,
		"visibility": "public",
		"enabled":    true,
		"deleted":    false,
		"version":    1,
	})
	topologyID := innerResult.InsertedID.(primitive.ObjectID).Hex()
	innerResult, _ = mongo.Collection(config.MongoDB.NodeColl).InsertOne(nil, bson.M{
		"name":     nodeCollection,
		"topology": topologyID,
		"enabled":  true,
		"type":     "start",
		"deleted":  false,
	})
	result[topologySuccessNodeSuccess] = []string{topologyID, innerResult.InsertedID.(primitive.ObjectID).Hex()}

	innerResult, _ = mongo.Collection(config.MongoDB.TopologyColl).InsertOne(nil, bson.M{
		"name":       topologyCollection,
		"visibility": "public",
		"enabled":    true,
		"deleted":    false,
		"version":    1,
	})
	topologyID = innerResult.InsertedID.(primitive.ObjectID).Hex()
	innerResult, _ = mongo.Collection(config.MongoDB.NodeColl).InsertOne(nil, bson.M{
		"name":     nodeCollection,
		"topology": topologyID,
		"enabled":  true,
		"type":     "webhook",
		"deleted":  false,
	})
	result[topologyWebhook] = []string{topologyID, innerResult.InsertedID.(primitive.ObjectID).Hex()}

	innerResult, _ = mongo.Collection(config.MongoDB.TopologyColl).InsertOne(nil, bson.M{
		"name":       topologyCollection,
		"visibility": "public",
		"enabled":    true,
		"deleted":    false,
		"version":    2,
	})
	topologyID = innerResult.InsertedID.(primitive.ObjectID).Hex()
	innerResult, _ = mongo.Collection(config.MongoDB.NodeColl).InsertOne(nil, bson.M{
		"name":     nodeCollection,
		"topology": topologyID,
		"enabled":  true,
		"type":     "start",
		"deleted":  false,
	})
	result[topologySuccessNodeSuccess] = []string{topologyID, innerResult.InsertedID.(primitive.ObjectID).Hex()}

	innerResult, _ = mongo.Collection(config.MongoDB.TopologyColl).InsertOne(nil, bson.M{
		"name":       topologyCollection,
		"visibility": "private",
		"enabled":    true,
		"deleted":    false,
		"version":    3,
	})
	topologyID = innerResult.InsertedID.(primitive.ObjectID).Hex()
	innerResult, _ = mongo.Collection(config.MongoDB.NodeColl).InsertOne(nil, bson.M{
		"name":     nodeCollection,
		"topology": topologyID,
		"enabled":  true,
		"type":     "start",
		"deleted":  false,
	})
	result[topologyVisibilityNodeSuccess] = []string{topologyID, innerResult.InsertedID.(primitive.ObjectID).Hex()}

	innerResult, _ = mongo.Collection(config.MongoDB.TopologyColl).InsertOne(nil, bson.M{
		"name":       topologyCollection,
		"visibility": "public",
		"enabled":    false,
		"deleted":    false,
		"version":    4,
	})
	topologyID = innerResult.InsertedID.(primitive.ObjectID).Hex()
	innerResult, _ = mongo.Collection(config.MongoDB.NodeColl).InsertOne(nil, bson.M{
		"name":     nodeCollection,
		"topology": topologyID,
		"enabled":  true,
		"type":     "start",
		"deleted":  false,
	})
	result[topologyEnabledNodeSuccess] = []string{topologyID, innerResult.InsertedID.(primitive.ObjectID).Hex()}

	innerResult, _ = mongo.Collection(config.MongoDB.TopologyColl).InsertOne(nil, bson.M{
		"name":       topologyCollection,
		"visibility": "public",
		"enabled":    true,
		"deleted":    true,
		"version":    5,
	})
	topologyID = innerResult.InsertedID.(primitive.ObjectID).Hex()
	innerResult, _ = mongo.Collection(config.MongoDB.NodeColl).InsertOne(nil, bson.M{
		"name":     nodeCollection,
		"topology": topologyID,
		"enabled":  true,
		"type":     "start",
		"deleted":  false,
	})
	result[topologyDeletedNodeSuccess] = []string{topologyID, innerResult.InsertedID.(primitive.ObjectID).Hex()}

	innerResult, _ = mongo.Collection(config.MongoDB.TopologyColl).InsertOne(nil, bson.M{
		"name":       topologyCollection,
		"visibility": "public",
		"enabled":    false,
		"deleted":    false,
		"version":    6,
	})
	topologyID = innerResult.InsertedID.(primitive.ObjectID).Hex()
	innerResult, _ = mongo.Collection(config.MongoDB.NodeColl).InsertOne(nil, bson.M{
		"name":     nodeCollection,
		"topology": topologyID,
		"enabled":  false,
		"type":     "start",
		"deleted":  false,
	})
	result[topologySuccessNodeEnabled] = []string{topologyID, innerResult.InsertedID.(primitive.ObjectID).Hex()}

	innerResult, _ = mongo.Collection(config.MongoDB.TopologyColl).InsertOne(nil, bson.M{
		"name":       topologyCollection,
		"visibility": "public",
		"enabled":    false,
		"deleted":    false,
		"version":    7,
	})
	topologyID = innerResult.InsertedID.(primitive.ObjectID).Hex()
	innerResult, _ = mongo.Collection(config.MongoDB.NodeColl).InsertOne(nil, bson.M{
		"name":     nodeCollection,
		"topology": topologyID,
		"enabled":  true,
		"type":     "start",
		"deleted":  true,
	})
	result[topologySuccessNodeDeleted] = []string{topologyID, innerResult.InsertedID.(primitive.ObjectID).Hex()}

	innerResult, _ = mongo.Collection(config.MongoDB.TopologyColl).InsertOne(nil, bson.M{
		"name":       topologyCollection,
		"visibility": "public",
		"enabled":    true,
		"deleted":    false,
		"version":    8,
	})
	topologyID = innerResult.InsertedID.(primitive.ObjectID).Hex()
	innerResult, _ = mongo.Collection(config.MongoDB.NodeColl).InsertOne(nil, bson.M{
		"name":     nodeCollection,
		"topology": topologyID,
		"enabled":  true,
		"type":     "start",
		"deleted":  false,
	})
	nodeID := innerResult.InsertedID.(primitive.ObjectID).Hex()

	innerResult, _ = mongo.Collection(config.MongoDB.WebhookColl).InsertOne(nil, bson.M{
		"user":        "User",
		"token":       "Token",
		"node":        "Node",
		"topology":    "Topology",
		"application": "Application",
	})

	result[topologySuccessWebhookSuccess] = []string{topologyID, nodeID, innerResult.InsertedID.(primitive.ObjectID).Hex()}

	return result
}
