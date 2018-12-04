package storage

import (
	"context"
	"fmt"
	"github.com/mongodb/mongo-go-driver/bson"
	"github.com/mongodb/mongo-go-driver/bson/objectid"
	"github.com/mongodb/mongo-go-driver/mongo"
	"starting-point/pkg/config"
	"strconv"
	"time"

	log "github.com/sirupsen/logrus"
)

// MongoInterface represents MongoDB database interface
type MongoInterface interface {
	Connect()
	Disconnect() error
	FindNodeByID(nodeID, topologyID string) *Node
	FindNodeByName(nodeName, topologyID string) []Node
	FindTopologyByID(topologyID, nodeID string) *Topology
	FindTopologyByName(topologyName, nodeName string) []Topology
}

// MongoDefault represents default MongoDB implementation
type MongoDefault struct {
	mongo            *mongo.Database
	keepAlive        *time.Ticker
	visibilityFilter bson.E
	enabledFilter    bson.E
	deletedFilter    bson.E
}

// Mongo represents default MongoDB implementation
var Mongo MongoInterface

// CreateMongo creates default MongoDB implementation
func CreateMongo() {
	Mongo = &MongoDefault{
		visibilityFilter: bson.E{Key: "visibility", Value: "public"},
		enabledFilter:    bson.E{Key: "enabled", Value: true},
		deletedFilter:    bson.E{Key: "deleted", Value: false},
	}
	Mongo.Connect()
}

// Connect connects to database
func (m *MongoDefault) Connect() {
	client, err := mongo.NewClient(fmt.Sprintf("mongodb://%s/%s", config.Config.MongoDB.Hostname, config.Config.MongoDB.Database))
	if err != nil {
		log.Error(err)
	}

	timeout, _ := strconv.Atoi(config.Config.MongoDB.Timeout)
	timeoutDuration := time.Duration(timeout) * time.Second
	innerContext, cancel := context.WithTimeout(context.Background(), timeoutDuration)
	defer cancel()

	err = client.Connect(innerContext)
	if err != nil {
		log.Error(err)
	}

	m.mongo = client.Database(config.Config.MongoDB.Database)
	m.keepAlive = time.NewTicker(timeoutDuration)

	go func() {
		for t := range m.keepAlive.C {
			log.Debug(fmt.Sprintf("MongoDB keep-alive (%s).", t))

			timeout, _ := strconv.Atoi(config.Config.MongoDB.Timeout)
			timeoutDuration := time.Duration(timeout) * time.Second
			innerContext, cancel := context.WithTimeout(context.Background(), timeoutDuration)
			defer cancel()

			err := m.mongo.Client().Ping(innerContext, nil)
			if err != nil {
				log.Errorf("MongoDB not connected. Reconnecting in %d seconds.", timeout)
			}
		}
	}()

	log.Infof("Connecting MongoDB to %s...", m.mongo.Client().ConnectionString())
}

// Disconnect disconnects from database
func (m *MongoDefault) Disconnect() error {
	return m.mongo.Client().Disconnect(nil)
}

// FindNodeByID finds node by id
func (m *MongoDefault) FindNodeByID(nodeID, topologyID string) *Node {
	var node Node
	timeout, _ := strconv.Atoi(config.Config.MongoDB.Timeout)
	timeoutDuration := time.Duration(timeout) * time.Second
	innerContext, cancel := context.WithTimeout(context.Background(), timeoutDuration)
	defer cancel()

	innerNodeID, err := objectid.FromHex(nodeID)
	if err != nil {
		log.Error(err)

		return nil
	}

	err = m.mongo.Collection(config.Config.MongoDB.NodeColl).FindOne(innerContext, bson.D{
		{"_id", innerNodeID},
		{"topology", topologyID},
		m.enabledFilter,
		m.deletedFilter,
	}).Decode(&node)
	if err != nil {
		log.Error(err)

		return nil
	}

	return &node
}

// FindNodeByName finds node by name
func (m *MongoDefault) FindNodeByName(nodeName, topologyID string) []Node {
	var node Node
	var nodes []Node
	timeout, _ := strconv.Atoi(config.Config.MongoDB.Timeout)
	timeoutDuration := time.Duration(timeout) * time.Second
	innerContext, cancel := context.WithTimeout(context.Background(), timeoutDuration)
	defer cancel()

	cursor, err := m.mongo.Collection(config.Config.MongoDB.NodeColl).Find(innerContext, bson.D{
		{"name", nodeName},
		{"topology", topologyID},
		m.enabledFilter,
		m.deletedFilter,
	})
	if err != nil {
		log.Error(err)

		return nodes
	}

	defer func() {
		_ = cursor.Close(nil)
	}()

	for cursor.Next(nil) {
		err = cursor.Decode(&node)

		if err != nil {
			log.Error(err)

			return nil
		}

		nodes = append(nodes, node)
	}

	return nodes
}

// FindTopologyByID finds topology by ID
func (m *MongoDefault) FindTopologyByID(topologyID, nodeID string) *Topology {
	var topology Topology
	timeout, _ := strconv.Atoi(config.Config.MongoDB.Timeout)
	timeoutDuration := time.Duration(timeout) * time.Second
	innerContext, cancel := context.WithTimeout(context.Background(), timeoutDuration)
	defer cancel()

	innerTopologyID, err := objectid.FromHex(topologyID)
	if err != nil {
		log.Error(err)

		return nil
	}

	err = m.mongo.Collection(config.Config.MongoDB.TopologyColl).FindOne(innerContext, bson.D{
		{"_id", innerTopologyID},
		m.visibilityFilter,
		m.enabledFilter,
		m.deletedFilter,
	}).Decode(&topology)
	if err != nil {
		log.Error(err)

		return nil
	}

	topology.Node = m.FindNodeByID(nodeID, topologyID)

	return &topology
}

// FindTopologyByName finds topology by name
func (m *MongoDefault) FindTopologyByName(topologyName, nodeName string) []Topology {
	var topology Topology
	var topologies []Topology
	timeout, _ := strconv.Atoi(config.Config.MongoDB.Timeout)
	timeoutDuration := time.Duration(timeout) * time.Second
	innerContext, cancel := context.WithTimeout(context.Background(), timeoutDuration)
	defer cancel()

	cursor, err := m.mongo.Collection(config.Config.MongoDB.TopologyColl).Find(innerContext, bson.D{
		{"name", topologyName},
		m.visibilityFilter,
		m.enabledFilter,
		m.deletedFilter,
	})
	if err != nil {
		log.Error(err)
	}

	defer func() {
		_ = cursor.Close(nil)
	}()

	for cursor.Next(nil) {
		err = cursor.Decode(&topology)
		if err != nil {
			log.Error(err)

			return nil
		}

		nodes := m.FindNodeByName(nodeName, topology.ID.Hex())
		if len(nodes) > 0 {
			topology.Node = &nodes[0]
			topologies = append(topologies, topology)
		}
	}

	return topologies
}
