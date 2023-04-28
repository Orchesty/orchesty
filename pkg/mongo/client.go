package mongo

import (
	"github.com/hanaboso/go-mongodb"
	"github.com/hanaboso/go-utils/pkg/contextx"
	"github.com/rs/zerolog/log"
	"go.mongodb.org/mongo-driver/mongo"
	"limiter/pkg/config"
)

type MongoSvc struct {
	connection         *mongodb.Connection
	collection         *mongo.Collection
	userTaskCollection *mongo.Collection
}

func NewMongoSvc() MongoSvc {
	connection := &mongodb.Connection{}
	connection.Connect(config.MongoDb.Dsn)
	database := connection.Database.Collection(config.MongoDb.MessageCollection)

	_, err := database.Indexes().CreateMany(contextx.WithTimeoutSecondsCtx(60), indices())
	if err != nil {
		log.Fatal().Err(err).Send()
	}

	return MongoSvc{
		connection:         connection,
		collection:         database,
		userTaskCollection: connection.Database.Collection(config.MongoDb.UserTaskCollection),
	}
}

func (this MongoSvc) Collection() *mongo.Collection {
	return this.collection
}

func (this MongoSvc) Connection() *mongodb.Connection {
	return this.connection
}
