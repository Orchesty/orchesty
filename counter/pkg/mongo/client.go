package mongo

import (
	"github.com/hanaboso/go-mongodb"
	"github.com/hanaboso/pipes/counter/pkg/config"
	"github.com/rs/zerolog/log"
	"go.mongodb.org/mongo-driver/v2/bson"
	"go.mongodb.org/mongo-driver/v2/mongo"
	"go.mongodb.org/mongo-driver/v2/mongo/options"
)

type MongoDb struct {
	connection *mongodb.Connection
}

func NewMongo() MongoDb {
	mongoDbCon := &mongodb.Connection{}
	mongoDbCon.Connect(config.MongoDb.Dsn)

	month := int32(2_628_000)
	ctx, cancel := mongoDbCon.Context()

	indexFinished := mongo.IndexModel{
		Keys: bson.M{
			"finished": 1,
		},
		Options: options.Index().SetName("IK_multiCounter_finished"),
	}
	indexExpires := mongo.IndexModel{
		Keys: bson.M{
			"created": 1,
		},
		Options: options.Index().SetExpireAfterSeconds(month).SetName("IK_multiCounter_created"),
	}

	coll := mongoDbCon.Database.Collection(config.MongoDb.CounterCollection)
	if _, err := coll.Indexes().CreateMany(ctx, []mongo.IndexModel{indexFinished, indexExpires}); err != nil {
		log.Err(err).Send()
	}

	indexCorr := mongo.IndexModel{
		Keys: bson.M{
			"correlationId": 1,
		},
		Options: options.Index().SetName("IK_multiCounterError_correlationId"),
	}
	indexExpires = mongo.IndexModel{
		Keys: bson.M{
			"created": 1,
		},
		Options: options.Index().SetExpireAfterSeconds(month).SetName("IK_multiCounterError_created"),
	}

	coll = mongoDbCon.Database.Collection(config.MongoDb.CounterSubCollection)
	if _, err := coll.Indexes().CreateMany(ctx, []mongo.IndexModel{indexCorr, indexExpires}); err != nil {
		log.Err(err).Send()
	}

	indexCorr = mongo.IndexModel{
		Keys: bson.M{
			"correlationId": 1,
		},
		Options: options.Index().SetName("IK_multiCounterError_correlationId"),
	}
	indexPro := mongo.IndexModel{
		Keys: bson.M{
			"processId": 1,
		},
		Options: options.Index().SetName("IK_multiCounterError_processId"),
	}
	indexExpires = mongo.IndexModel{
		Keys: bson.M{
			"created": 1,
		},
		Options: options.Index().SetExpireAfterSeconds(month).SetName("IK_multiCounterError_created"),
	}

	coll = mongoDbCon.Database.Collection(config.MongoDb.CounterErrCollection)
	if _, err := coll.Indexes().CreateMany(ctx, []mongo.IndexModel{indexCorr, indexPro, indexExpires}); err != nil {
		log.Err(err).Send()
	}

	cancel()

	return MongoDb{
		connection: mongoDbCon,
	}
}

func (m *MongoDb) Close() {
	m.connection.Disconnect()
}

func getId(id string) bson.ObjectID {
	oid, _ := bson.ObjectIDFromHex(id)
	return oid
}
