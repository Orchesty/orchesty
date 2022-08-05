package mongo

import (
	"github.com/hanaboso/go-mongodb"
	"github.com/hanaboso/pipes/bridge/pkg/config"
	"github.com/hanaboso/pipes/bridge/pkg/model"
	"go.mongodb.org/mongo-driver/mongo"
)

type MongoDb struct {
	connection *mongodb.Connection
	collection *mongo.Collection
}

func (m *MongoDb) StoreUserTask(dto model.ProcessResult, nodeName, topologyName string) error {
	document := fromDto(dto, nodeName, topologyName)
	ctx, cancel := m.connection.Context()
	_, err := m.collection.InsertOne(ctx, document)
	cancel()

	return err
}

func (m *MongoDb) Close() {
	m.connection.Disconnect()
}

func NewMongoDb() *MongoDb {
	mongoDb := &mongodb.Connection{}
	mongoDb.Connect(config.MongoDb.Dsn)
	return &MongoDb{
		connection: mongoDb,
		collection: mongoDb.Database.Collection(config.MongoDb.UserTaskCollection),
	}
}
