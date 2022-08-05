package storage

import (
	"github.com/hanaboso/go-mongodb"
	"go.mongodb.org/mongo-driver/bson/primitive"
	"topology-generator/pkg/config"
	"topology-generator/pkg/model"

	log "github.com/hanaboso/go-log/pkg"
)

// MongoInterface represents MongoDB database interface
type MongoInterface interface {
	Connect()
	Disconnect()
	FindTopologyByID(id string) (*model.Topology, error)
	FindNodesByTopology(id string) ([]model.Node, error)
}

// MongoDefault represents default MongoDB implementation
type MongoDefault struct {
	mongo            *mongodb.Connection
	logger           log.Logger
	visibilityFilter primitive.E
	enabledFilter    primitive.E
	deletedFilter    primitive.E
	MongoInterface
}

// Mongo represents default MongoDB implementation
var Mongo MongoInterface

// CreateMongo creates default MongoDB implementation
func CreateMongo() *MongoDefault {
	m := &MongoDefault{
		mongo:            nil,
		logger:           config.Logger,
		visibilityFilter: primitive.E{Key: "visibility", Value: "public"},
		enabledFilter:    primitive.E{Key: "enabled", Value: true},
		deletedFilter:    primitive.E{Key: "deleted", Value: false},
	}
	m.Connect()

	return m
}

// Connect connects to database
func (m *MongoDefault) Connect() {
	m.logContext(nil).Info("Connecting to MongoDB: %s", config.Mongo.Dsn)
	connection := &mongodb.Connection{}
	connection.Connect(config.Mongo.Dsn)

	m.mongo = connection
	m.logContext(nil).Info("MongoDB successfully connected!")
}

// Disconnect disconnects from database
func (m *MongoDefault) Disconnect() {
	m.mongo.Disconnect()
}

// FindTopologyByID FindTopologyByID
func (m *MongoDefault) FindTopologyByID(id string) (*model.Topology, error) {
	var topology model.Topology
	innerContext, cancel := m.mongo.Context()
	defer cancel()

	innerTopologyID, err := primitive.ObjectIDFromHex(id)
	if err != nil {
		m.logContext(map[string]interface{}{
			"topologyId": id,
		}).Warn("Topology ID is not valid MongoDB ID.")

		return nil, err
	}

	filter := primitive.D{
		{Key: "_id", Value: innerTopologyID},
		m.deletedFilter,
	}

	err = m.mongo.Database.Collection(config.Mongo.Topology).FindOne(innerContext, filter).Decode(&topology)

	if err != nil {
		m.logContext(map[string]interface{}{
			"topologyId": id,
		}).Warn("Topology not found.")

		return nil, err
	}

	return &topology, nil
}

// FindNodesByTopology FindNodesByTopology
func (m *MongoDefault) FindNodesByTopology(id string) ([]model.Node, error) {
	var nodes []model.Node

	innerContext, cancel := m.mongo.Context()
	defer cancel()

	filter := primitive.D{
		{Key: "topology", Value: id},
		m.deletedFilter,
	}

	cursor, err := m.mongo.Database.Collection(config.Mongo.Node).Find(innerContext, filter)

	if err != nil {
		m.logContext(map[string]interface{}{
			"topologyId": id,
		}).Warn("Topology not found.")

		return nil, err
	}

	defer func() {
		_ = cursor.Close(nil)
	}()

	for cursor.Next(nil) {
		var node model.Node
		err = cursor.Decode(&node)

		if err != nil {
			m.logContext(map[string]interface{}{
				"reason":   "Node name decode",
				"nodeName": node.Name,
			}).Error(err)
			return nil, err
		}

		nodes = append(nodes, node)
	}

	return nodes, nil
}

func (m *MongoDefault) logContext(data map[string]interface{}) log.Logger {
	if data == nil {
		data = make(map[string]interface{})
	}

	data["service"] = "topology-generator"
	data["type"] = "mongodb"

	return m.logger.WithFields(data)
}
