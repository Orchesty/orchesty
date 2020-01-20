package storage

import (
	"context"
	"time"

	log "github.com/sirupsen/logrus"
	"go.mongodb.org/mongo-driver/bson/primitive"
	"go.mongodb.org/mongo-driver/mongo"
	"go.mongodb.org/mongo-driver/mongo/options"
	"go.mongodb.org/mongo-driver/mongo/readpref"

	"topology-generator/pkg/config"
	"topology-generator/pkg/model"
)

// MongoInterface represents MongoDB database interface
type MongoInterface interface {
	Connect()
	Disconnect() error
	FindTopologyByID(id string) (*model.Topology, error)
	FindNodesByTopology(id string) ([]model.Node, error)
}

// MongoDefault represents default MongoDB implementation
type MongoDefault struct {
	mongo            *mongo.Database
	keepAlive        *time.Ticker
	log              *log.Logger
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
		keepAlive:        time.NewTicker(time.Minute),
		log:              log.New(),
		visibilityFilter: primitive.E{Key: "visibility", Value: "public"},
		enabledFilter:    primitive.E{Key: "enabled", Value: true},
		deletedFilter:    primitive.E{Key: "deleted", Value: false},
	}
	m.Connect()

	return m
}

func dataSourceName() string {
	return config.Mongo.Host
}

// Connect connects to database
func (m *MongoDefault) Connect() {
	ds := dataSourceName()
	client, err := mongo.NewClient(options.Client().ApplyURI(ds))
	if err != nil {
		m.log.Errorf("MongoDB connect: %s", err.Error())
		return
	}

	timeoutDuration := config.Mongo.Timeout * time.Minute
	innerContext, cancel := createContextWithTimeout()
	defer cancel()

	err = client.Connect(innerContext)
	if err != nil {
		m.log.Errorf("MongoDB connect: %s", err.Error())
		return
	}

	ctx, cancelFunc := context.WithTimeout(context.Background(), 10*time.Second)

	defer cancelFunc()
	e := client.Ping(ctx, readpref.Primary())
	log.Debugf("Connecting MongoDB ping result: %b...", e)
	m.mongo = client.Database(config.Mongo.Database)
	m.keepAlive = time.NewTicker(timeoutDuration)

	go func() {
		for t := range m.keepAlive.C {
			m.log.Debugf("MongoDB keep-alive: %s", t)
			innerContext, cancel := createContextWithTimeout()

			err := m.mongo.Client().Ping(innerContext, nil)
			if err != nil {
				m.log.Errorf("MongoDB not connected. Reconnecting in %d seconds.", config.Mongo.Timeout)
			}

			cancel()
		}
	}()

	log.Infof("Connecting MongoDB to %s...", ds)
}

// Disconnect disconnects from database
func (m *MongoDefault) Disconnect() error {
	innerContext, cancel := createContextWithTimeout()
	defer cancel()

	return m.mongo.Client().Disconnect(innerContext)
}

func (m *MongoDefault) FindTopologyByID(id string) (*model.Topology, error) {
	var topology model.Topology
	innerContext, cancel := createContextWithTimeout()
	defer cancel()

	innerTopologyID, err := primitive.ObjectIDFromHex(id)
	if err != nil {
		m.log.Warnf("Topology ID '%s' is not valid MongoDB ID.", id)

		return nil, err
	}

	filter := primitive.D{
		{Key: "_id", Value: innerTopologyID},
		m.deletedFilter,
	}

	err = m.mongo.Collection(config.Mongo.Topology).FindOne(innerContext, filter).Decode(&topology)

	if err != nil {
		m.log.Warnf("Topology with key '%s' not found.", id)

		return nil, err
	}

	return &topology, nil
}

func (m *MongoDefault) FindNodesByTopology(id string) ([]model.Node, error) {
	var nodes []model.Node

	innerContext, cancel := createContextWithTimeout()
	defer cancel()

	filter := primitive.D{
		{Key: "topology", Value: id},
		m.deletedFilter,
	}

	cursor, err := m.mongo.Collection(config.Mongo.Node).Find(innerContext, filter)

	if err != nil {
		m.log.Warnf("Topology with key '%s' not found.", id)

		return nil, err
	}

	defer func() {
		_ = cursor.Close(nil)
	}()

	for cursor.Next(nil) {
		var node model.Node
		err = cursor.Decode(&node)

		if err != nil {
			m.log.Errorf("Node with name '%s' decode error: %s", node.Name, err.Error())
			return nil, err
		}

		nodes = append(nodes, node)
	}

	return nodes, nil

}

func createContextWithTimeout() (context.Context, context.CancelFunc) {
	timeoutDuration := config.Mongo.Timeout * time.Second

	return context.WithTimeout(context.Background(), timeoutDuration)
}
