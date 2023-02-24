package storage

import (
	"fmt"
	"starting-point/pkg/enum"

	log "github.com/hanaboso/go-log/pkg"
	"github.com/hanaboso/go-mongodb"
	"go.mongodb.org/mongo-driver/bson/primitive"
	"go.mongodb.org/mongo-driver/mongo/options"
	"starting-point/pkg/config"
)

// MongoInterface represents MongoDB database interface
type MongoInterface interface {
	Connect()
	Disconnect()
	IsConnected() bool
	DropApiTokenCollection() error
	InsertApiToken(user string, scopes []string, key string) error
	FindApiKeyByUserAndScopes(user string, scopes []string) (string, error)
	FindNodeByID(nodeID, topologyID string, uiRun bool, allowedTypes []string) *Node
	FindNodeByName(nodeName, topologyID string) []Node
	FindTopologyByID(topologyID, nodeID string, uiRun bool, allowedTypes []string) *Topology
	FindTopologyByName(topologyName, nodeName string) *Topology
	FindTopologyByApplication(topologyName, nodeName, token string) (*Topology, *Webhook)
}

// MongoDefault represents default MongoDB implementation
type MongoDefault struct {
	connection       *mongodb.Connection
	log              log.Logger
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
		log:              config.Logger,
	}

	Mongo.Connect()
}

// Connect connects to database
func (m *MongoDefault) Connect() {
	m.log.Info("Connecting to MongoDB: %s", config.MongoDB.Dsn)

	m.connection = &mongodb.Connection{}
	m.connection.Connect(config.MongoDB.Dsn)

	m.log.Info("MongoDB successfully connected!")
}

// Disconnect disconnects from database
func (m *MongoDefault) Disconnect() {
	m.connection.Disconnect()
}

// IsConnected checks connection status
func (m *MongoDefault) IsConnected() bool {
	return m.connection.IsConnected()
}

func (m *MongoDefault) DropApiTokenCollection() error {
	context, cancel := m.connection.Context()
	defer cancel()

	err := m.connection.Database.Collection(config.MongoDB.ApiTokenColl).Drop(context)

	if err != nil {
		logMongoError(m.log, err, "Could not Drop ApiToken!")
	}

	return err
}

func (m *MongoDefault) InsertApiToken(user string, scopes []string, key string) error {
	context, cancel := m.connection.Context()
	defer cancel()

	_, err := m.connection.Database.
		Collection(config.MongoDB.ApiTokenColl).
		InsertOne(context, map[string]interface{}{"user": user, "scopes": scopes, "key": key})

	if err != nil {
		logMongoError(m.log, err, "Could not create ApiToken!")
	}

	return err
}

func (m *MongoDefault) FindApiKeyByUserAndScopes(user string, scopes []string) (string, error) {
	var apiToken ApiToken
	context, cancel := m.connection.Context()
	defer cancel()

	err := m.connection.Database.
		Collection(config.MongoDB.ApiTokenColl).
		FindOne(context, map[string]interface{}{"user": user, "scopes": scopes}).
		Decode(&apiToken)

	if err != nil {
		return "", err
	}

	return apiToken.Key, err
}

// FindNodeByID finds node by id
func (m *MongoDefault) FindNodeByID(nodeID, topologyID string, uiRun bool, allowedTypes []string) *Node {
	var node Node
	innerContext, cancel := m.connection.Context()
	defer cancel()

	innerNodeID, err := primitive.ObjectIDFromHex(nodeID)
	if err != nil {
		m.log.Warn("Node ID '%s' is not valid MongoDB ID.", nodeID)

		return nil
	}

	filter := primitive.D{
		{"_id", innerNodeID},
		{"topology", topologyID},
		{"type", primitive.M{"$in": allowedTypes}},
		m.deletedFilter,
	}

	if !uiRun {
		filter = append(filter, m.enabledFilter)
	}

	err = m.connection.Database.Collection(config.MongoDB.NodeColl).FindOne(innerContext, filter).Decode(&node)
	if err != nil {
		logMongoError(m.log, err, fmt.Sprintf("Node with key '%s' not found.", nodeID))

		return nil
	}

	return &node
}

// FindNodeByName finds node by name
func (m *MongoDefault) FindNodeByName(nodeName, topologyID string) []Node {
	var node Node
	var nodes []Node
	innerContext, cancel := m.connection.Context()
	defer cancel()

	cursor, err := m.connection.Database.Collection(config.MongoDB.NodeColl).Find(innerContext, primitive.D{
		{"name", nodeName},
		{"topology", topologyID},
		{"type", primitive.M{"$in": []string{enum.NodeType_Start, enum.NodeType_Cron}}},
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
			m.log.Error(fmt.Errorf("node with name '%s' decode error: %s", nodeName, err.Error()))

			return nil
		}

		nodes = append(nodes, node)
	}

	return nodes
}

// FindTopologyByID finds topology by ID
func (m *MongoDefault) FindTopologyByID(topologyID, nodeID string, uiRun bool, allowedTypes []string) *Topology {
	var topology Topology
	innerContext, cancel := m.connection.Context()
	defer cancel()

	innerTopologyID, err := primitive.ObjectIDFromHex(topologyID)
	if err != nil {
		m.log.Warn("Topology ID '%s' is not valid MongoDB ID.", topologyID)

		return nil
	}

	var filters = primitive.D{
		{"_id", innerTopologyID},
		m.visibilityFilter,
		m.deletedFilter,
	}
	if !uiRun {
		filters = append(filters, m.enabledFilter)
	}

	err = m.connection.Database.Collection(config.MongoDB.TopologyColl).FindOne(innerContext, filters).Decode(&topology)
	if err != nil {
		logMongoError(m.log, err, fmt.Sprintf("Topology with key '%s' not found.", topologyID))

		return nil
	}

	topology.Node = m.FindNodeByID(nodeID, topologyID, uiRun, allowedTypes)

	return &topology
}

// FindTopologyByName finds topology by name
func (m *MongoDefault) FindTopologyByName(topologyName, nodeName string) *Topology {
	var topology Topology
	innerContext, cancel := m.connection.Context()
	defer cancel()

	cursor, err := m.connection.Database.Collection(config.MongoDB.TopologyColl).Find(innerContext, primitive.D{
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
			m.log.Error(fmt.Errorf("topology with name '%s' decode error: %s", topologyName, err.Error()))

			return nil
		}

		nodes := m.FindNodeByName(nodeName, topology.ID.Hex())
		if len(nodes) > 0 {
			topology.Node = &nodes[0]
			return &topology
		}
	}

	return nil
}

// FindTopologyByApplication finds topology by application
func (m *MongoDefault) FindTopologyByApplication(topologyName, nodeName, token string) (*Topology, *Webhook) {
	var webhook Webhook
	innerContext, cancel := m.connection.Context()
	defer cancel()

	err := m.connection.Database.Collection(config.MongoDB.WebhookColl).FindOne(innerContext, primitive.D{
		{"topology", topologyName},
		{"node", nodeName},
		{"token", token},
	}).Decode(&webhook)

	if err != nil {
		logMongoError(m.log, err, fmt.Sprintf("Webhook with token '%s' decode error: %s", token, err.Error()))

		return nil, nil
	}

	topology := m.FindTopologyByName(topologyName, nodeName)

	return topology, &webhook
}

func logMongoError(log log.Logger, err error, content string) {
	if err.Error() == "mongo: no documents in result" {
		log.Warn(content)
	} else {
		log.Error(fmt.Errorf("unexpected MongoDB error: %s", err.Error()))
	}
}
