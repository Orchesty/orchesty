package mongo

import (
	"github.com/hanaboso/go-mongodb"
	"github.com/hanaboso/pipes/counter/pkg/config"
	"go.mongodb.org/mongo-driver/bson"
	"go.mongodb.org/mongo-driver/mongo"
	"go.mongodb.org/mongo-driver/mongo/options"
)

type MongoDb struct {
	connection *mongodb.Connection
	collection *mongo.Collection
}

func NewMongo() *MongoDb {
	mongoDbCon := &mongodb.Connection{}
	mongoDbCon.Connect(config.MongoDb.Dsn)

	mongoDb := &MongoDb{
		connection: mongoDbCon,
		collection: mongoDbCon.Database.Collection(config.MongoDb.CounterCollection),
	}

	indexCorr := mongo.IndexModel{
		Keys: bson.M{
			"correlationId": 1,
		},
		Options: nil,
	}
	indexFinished := mongo.IndexModel{
		Keys: bson.M{
			"finished": 1,
		},
		Options: nil,
	}
	month := int32(30 * 24 * 60 * 60)
	indexExpires := mongo.IndexModel{
		Keys: bson.M{
			"created": 1,
		},
		Options: &options.IndexOptions{
			ExpireAfterSeconds: &month,
		},
	}
	ctx, cancel := mongoDb.connection.Context()
	if _, err := mongoDb.collection.Indexes().CreateMany(ctx, []mongo.IndexModel{indexCorr, indexFinished, indexExpires}); err != nil {
		mongoDb.connection.Log.Error(err)
	}
	cancel()

	return mongoDb
}

func (m *MongoDb) Close() {
	m.connection.Disconnect()
}
