package storage

import (
	"fmt"

	"github.com/hanaboso/go-mongodb"
	"go.mongodb.org/mongo-driver/bson/primitive"
	"go.mongodb.org/mongo-driver/mongo/options"
	"starting-point/pkg/config"

	log "github.com/sirupsen/logrus"
)

// MongoInterface represents MongoDB database interface
type MongoInterface interface {
	Connect()
	Disconnect()
	IsConnected() bool
	FindNodeByID(nodeID, topologyID, humanTaskID string, isHumanTask bool) *Node
	FindNodeByName(nodeName, topologyID, humanTaskID string, isHumanTask bool) []Node
	FindTopologyByID(topologyID, nodeID, humanTaskID string, isHumanTask bool) *Topology
	FindTopologyByName(topologyName, nodeName, humanTaskID string, isHumanTask bool) *Topology
	FindTopologyByApplication(topologyName, nodeName, token string) (*Topology, *Webhook)
	FindHumanTask(nodeID, topologyID, humanTaskID string) *HumanTask
}

// MongoDefault represents default MongoDB implementation
type MongoDefault struct {
	connection       *mongodb.Connection
	log              *log.Logger
	visibilityFilter primitive.E
	enabledFilter    primitive.E
	deletedFilter    primitive.E
	versionSort      primitive.D
}

// Mongo represents default MongoDB implementation
var Mongo MongoInterface

// CreateMongo creates default MongoDB implementation
func CreateMongo() {
	Mongo = &MongoDefault{
		visibilityFilter: primitive.E{Key: "visibility", Value: "public"},
		enabledFilter:    primitive.E{Key: "enabled", Value: true},
		deletedFilter:    primitive.E{Key: "deleted", Value: false},
		versionSort:      primitive.D{{"version", -1}},
		log:              config.Config.Logger,
	}

	Mongo.Connect()
}

// Connect connects to database
func (m *MongoDefault) Connect() {
	log.Infof("Connecting to MongoDB: %s", config.Config.MongoDB.Dsn)

	m.connection = &mongodb.Connection{}
	m.connection.Connect(config.Config.MongoDB.Dsn)

	log.Info("MongoDB successfully connected!")
}

// Disconnect disconnects from database
func (m *MongoDefault) Disconnect() {
	m.connection.Disconnect()
}

// IsConnected checks connection status
func (m *MongoDefault) IsConnected() bool {
	return m.connection.IsConnected()
}

// FindNodeByID finds node by id
func (m *MongoDefault) FindNodeByID(nodeID, topologyID, humanTaskID string, isHumanTask bool) *Node {
	var node Node
	innerContext, cancel := m.connection.Context()
	defer cancel()

	innerNodeID, err := primitive.ObjectIDFromHex(nodeID)
	if err != nil {
		m.log.Warnf("Node ID '%s' is not valid MongoDB ID.", nodeID)

		return nil
	}

	err = m.connection.Database.Collection(config.Config.MongoDB.NodeColl).FindOne(innerContext, primitive.D{
		{"_id", innerNodeID},
		{"topology", topologyID},
		m.enabledFilter,
		m.deletedFilter,
	}).Decode(&node)
	if err != nil {
		logMongoError(m.log, err, fmt.Sprintf("Node with key '%s' not found.", nodeID))

		return nil
	}

	return &node
}

// FindNodeByName finds node by name
func (m *MongoDefault) FindNodeByName(nodeName, topologyID, humanTaskID string, isHumanTask bool) []Node {
	var node Node
	var nodes []Node
	innerContext, cancel := m.connection.Context()
	defer cancel()

	cursor, err := m.connection.Database.Collection(config.Config.MongoDB.NodeColl).Find(innerContext, primitive.D{
		{"name", nodeName},
		{"topology", topologyID},
		m.enabledFilter,
		m.deletedFilter,
	})
	if err != nil {
		logMongoError(m.log, err, fmt.Sprintf("Node with name '%s' not found.", nodeName))

		return nodes
	}

	defer func() {
		_ = cursor.Close(nil)
	}()

	for cursor.Next(nil) {
		err = cursor.Decode(&node)

		if err != nil {
			m.log.Errorf("Node with name '%s' decode error: %s", nodeName, err.Error())

			return nil
		}

		nodes = append(nodes, node)
	}

	return nodes
}

// FindTopologyByID finds topology by ID
func (m *MongoDefault) FindTopologyByID(topologyID, nodeID, humanTaskID string, isHumanTask bool) *Topology {
	var topology Topology
	innerContext, cancel := m.connection.Context()
	defer cancel()

	innerTopologyID, err := primitive.ObjectIDFromHex(topologyID)
	if err != nil {
		m.log.Warnf("Topology ID '%s' is not valid MongoDB ID.", topologyID)

		return nil
	}

	err = m.connection.Database.Collection(config.Config.MongoDB.TopologyColl).FindOne(innerContext, primitive.D{
		{"_id", innerTopologyID},
		m.visibilityFilter,
		m.enabledFilter,
		m.deletedFilter,
	}).Decode(&topology)
	if err != nil {
		logMongoError(m.log, err, fmt.Sprintf("Topology with key '%s' not found.", topologyID))

		return nil
	}

	topology.Node = m.FindNodeByID(nodeID, topologyID, humanTaskID, isHumanTask)

	if isHumanTask && topology.Node != nil {
		topology.Node.HumanTask = m.FindHumanTask(nodeID, topologyID, humanTaskID)
	}

	return &topology
}

// FindTopologyByName finds topology by name
func (m *MongoDefault) FindTopologyByName(topologyName, nodeName, humanTaskID string, isHumanTask bool) *Topology {
	var topology Topology
	innerContext, cancel := m.connection.Context()
	defer cancel()

	cursor, err := m.connection.Database.Collection(config.Config.MongoDB.TopologyColl).Find(innerContext, primitive.D{
		{"name", topologyName},
		m.visibilityFilter,
		m.enabledFilter,
		m.deletedFilter,
	}, options.Find().SetSort(m.versionSort).SetLimit(1))
	if err != nil {
		logMongoError(m.log, err, fmt.Sprintf("Topology with name '%s' not found.", topologyName))
	}

	defer func() {
		_ = cursor.Close(nil)
	}()

	if cursor.Next(nil) {
		err = cursor.Decode(&topology)
		if err != nil {
			m.log.Errorf("Topology with name '%s' decode error: %s", topologyName, err.Error())

			return nil
		}

		nodes := m.FindNodeByName(nodeName, topology.ID.Hex(), humanTaskID, isHumanTask)
		if len(nodes) > 0 {
			topology.Node = &nodes[0]
			if isHumanTask {
				topology.Node.HumanTask = m.FindHumanTask(nodes[0].ID.Hex(), topology.ID.Hex(), humanTaskID)
				if topology.Node.HumanTask != nil {
					return &topology
				}
			} else {
				return &topology
			}
		}
	}

	return nil
}

// FindTopologyByApplication finds topology by application
func (m *MongoDefault) FindTopologyByApplication(topologyName, nodeName, token string) (*Topology, *Webhook) {
	var webhook Webhook
	innerContext, cancel := m.connection.Context()
	defer cancel()

	err := m.connection.Database.Collection(config.Config.MongoDB.WebhookColl).FindOne(innerContext, primitive.D{
		{"topology", topologyName},
		{"node", nodeName},
		{"token", token},
	}).Decode(&webhook)

	if err != nil {
		logMongoError(m.log, err, fmt.Sprintf("Webhook with token '%s' decode error: %s", token, err.Error()))

		return nil, nil
	}

	topology := m.FindTopologyByName(topologyName, nodeName, "", false)

	return topology, &webhook
}

// FindHumanTask finds human task
func (m *MongoDefault) FindHumanTask(nodeID, topologyID, humanTaskID string) *HumanTask {
	var humanTask HumanTask

	innerContext, cancel := m.connection.Context()
	defer cancel()

	var filter = primitive.D{
		primitive.E{Key: "topologyId", Value: topologyID},
		primitive.E{Key: "nodeId", Value: nodeID},
	}
	if humanTaskID != "" {
		humanTaskID, err := primitive.ObjectIDFromHex(humanTaskID)
		if err != nil {
			m.log.Warnf("HumanTask ID '%s' is not valid MongoDB ID.", humanTaskID)

			return nil
		}

		filter = append(filter, primitive.E{Key: "_id", Value: humanTaskID})
	}

	err := m.connection.Database.Collection(config.Config.MongoDB.HumanTaskColl).FindOne(innerContext, filter).Decode(&humanTask)
	if err != nil {
		logMongoError(m.log, err, fmt.Sprintf("HumanTask with topology '%s', node '%s' and process '%s' not found.", topologyID, nodeID, humanTaskID))

		return nil
	}

	return &humanTask
}

func logMongoError(log *log.Logger, err error, content string) {
	if err.Error() == "mongo: no documents in result" {
		log.Warn(content)
	} else {
		log.Errorf("Unexpected MongoDB error: %s", err.Error())
	}
}
