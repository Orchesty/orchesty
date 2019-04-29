package storage

import (
	"context"
	"fmt"
	"go.mongodb.org/mongo-driver/bson/primitive"
	"go.mongodb.org/mongo-driver/mongo"
	"go.mongodb.org/mongo-driver/mongo/options"
	"starting-point/pkg/config"
	"strconv"
	"time"

	log "github.com/sirupsen/logrus"
)

// MongoInterface represents MongoDB database interface
type MongoInterface interface {
	Connect()
	Disconnect() error
	FindNodeByID(nodeID, topologyID, processID string, isHumanTask bool) *Node
	FindNodeByName(nodeName, topologyID, processID string, isHumanTask bool) []Node
	FindTopologyByID(topologyID, nodeID, processID string, isHumanTask bool) *Topology
	FindTopologyByName(topologyName, nodeName, processID string, isHumanTask bool) []Topology
	FindHumanTask(nodeID, topologyID, processID string) *HumanTask
}

// MongoDefault represents default MongoDB implementation
type MongoDefault struct {
	mongo            *mongo.Database
	keepAlive        *time.Ticker
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
	client, err := mongo.NewClient(options.Client().ApplyURI(fmt.Sprintf("mongodb://%s", config.Config.MongoDB.Hostname)))
	if err != nil {
		m.log.Errorf("MongoDB connect: %s", err.Error())
	}

	timeout, _ := strconv.Atoi(config.Config.MongoDB.Timeout)
	timeoutDuration := time.Duration(timeout) * (time.Minute)
	innerContext, cancel := createContextWithTimeout()
	defer cancel()

	err = client.Connect(innerContext)
	if err != nil {
		m.log.Errorf("MongoDB connect: %s", err.Error())
	}

	m.mongo = client.Database(config.Config.MongoDB.Database)
	m.keepAlive = time.NewTicker(timeoutDuration)

	go func() {
		for t := range m.keepAlive.C {
			m.log.Debug(fmt.Sprintf("MongoDB keep-alive: %s", t))
			innerContext, cancel := createContextWithTimeout()

			err := m.mongo.Client().Ping(innerContext, nil)
			if err != nil {
				m.log.Errorf("MongoDB not connected. Reconnecting in %d seconds.", timeout)
			}

			cancel()
		}
	}()

	log.Info("Connecting to MongoDB...")
}

// Disconnect disconnects from database
func (m *MongoDefault) Disconnect() error {
	innerContext, cancel := createContextWithTimeout()
	defer cancel()

	return m.mongo.Client().Disconnect(innerContext)
}

// FindNodeByID finds node by id
func (m *MongoDefault) FindNodeByID(nodeID, topologyID, processID string, isHumanTask bool) *Node {
	var node Node
	innerContext, cancel := createContextWithTimeout()
	defer cancel()

	innerNodeID, err := primitive.ObjectIDFromHex(nodeID)
	if err != nil {
		m.log.Warnf("Node ID '%s' is not valid MongoDB ID.", nodeID)

		return nil
	}

	err = m.mongo.Collection(config.Config.MongoDB.NodeColl).FindOne(innerContext, primitive.D{
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
func (m *MongoDefault) FindNodeByName(nodeName, topologyID, processID string, isHumanTask bool) []Node {
	var node Node
	var nodes []Node
	innerContext, cancel := createContextWithTimeout()
	defer cancel()

	cursor, err := m.mongo.Collection(config.Config.MongoDB.NodeColl).Find(innerContext, primitive.D{
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
func (m *MongoDefault) FindTopologyByID(topologyID, nodeID, processID string, isHumanTask bool) *Topology {
	var topology Topology
	innerContext, cancel := createContextWithTimeout()
	defer cancel()

	innerTopologyID, err := primitive.ObjectIDFromHex(topologyID)
	if err != nil {
		m.log.Warnf("Topology ID '%s' is not valid MongoDB ID.", topologyID)

		return nil
	}

	err = m.mongo.Collection(config.Config.MongoDB.TopologyColl).FindOne(innerContext, primitive.D{
		{"_id", innerTopologyID},
		m.visibilityFilter,
		m.enabledFilter,
		m.deletedFilter,
	}).Decode(&topology)
	if err != nil {
		logMongoError(m.log, err, fmt.Sprintf("Topology with key '%s' not found.", topologyID))

		return nil
	}

	topology.Node = m.FindNodeByID(nodeID, topologyID, processID, isHumanTask)

	if isHumanTask && topology.Node != nil {
		topology.Node.HumanTask = m.FindHumanTask(nodeID, topologyID, processID)
	}

	return &topology
}

// FindTopologyByName finds topology by name
func (m *MongoDefault) FindTopologyByName(topologyName, nodeName, processID string, isHumanTask bool) []Topology {
	var topology Topology
	var topologies []Topology
	innerContext, cancel := createContextWithTimeout()
	defer cancel()

	cursor, err := m.mongo.Collection(config.Config.MongoDB.TopologyColl).Find(innerContext, primitive.D{
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

	for cursor.Next(nil) {
		err = cursor.Decode(&topology)
		if err != nil {
			m.log.Errorf("Topology with name '%s' decode error: %s", topologyName, err.Error())

			return nil
		}

		nodes := m.FindNodeByName(nodeName, topology.ID.Hex(), processID, isHumanTask)
		if len(nodes) > 0 {
			topology.Node = &nodes[0]
			if isHumanTask {
				topology.Node.HumanTask = m.FindHumanTask(nodes[0].ID.Hex(), topology.ID.Hex(), processID)
				if topology.Node.HumanTask != nil {
					topologies = append(topologies, topology)
				}
			} else {
				topologies = append(topologies, topology)
			}
		}
	}

	return topologies
}

// FindHumanTask finds human task
func (m *MongoDefault) FindHumanTask(nodeID, topologyID, processID string) *HumanTask {
	var humanTask HumanTask

	innerContext, cancel := createContextWithTimeout()
	defer cancel()

	var filter = primitive.D{
		primitive.E{Key: "topologyId", Value: topologyID},
		primitive.E{Key: "nodeId", Value: nodeID},
	}
	if processID != "" {
		filter = append(filter, primitive.E{Key: "processId", Value: processID})
	}

	err := m.mongo.Collection(config.Config.MongoDB.HumanTaskColl).FindOne(innerContext, filter).Decode(&humanTask)
	if err != nil {
		logMongoError(m.log, err, fmt.Sprintf("HumanTask with topology '%s', node '%s' and process '%s' not found.", topologyID, nodeID, processID))

		return nil
	}

	return &humanTask
}

func createContextWithTimeout() (context.Context, context.CancelFunc) {
	timeout, _ := strconv.Atoi(config.Config.MongoDB.Timeout)
	timeoutDuration := time.Duration(timeout) * time.Second

	return context.WithTimeout(context.Background(), timeoutDuration)
}

func logMongoError(log *log.Logger, err error, content string) {
	if err.Error() == "mongo: no documents in result" {
		log.Warn(content)
	} else {
		log.Errorf("Unexpected MongoDB error: %s", err.Error())
	}
}
