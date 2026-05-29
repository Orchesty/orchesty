package mongo

import (
	"limiter/pkg/config"

	"github.com/hanaboso/go-mongodb"
	"github.com/hanaboso/go-utils/pkg/contextx"
	"github.com/rs/zerolog/log"
	"go.mongodb.org/mongo-driver/v2/mongo"
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

	ctx, _ := contextx.WithTimeoutSecondsCtx(60)
	_, err := database.Indexes().CreateMany(ctx, indices())
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

func (this MongoSvc) UserTaskCollection() *mongo.Collection {
	return this.userTaskCollection
}

func (this MongoSvc) Connection() *mongodb.Connection {
	return this.connection
}
