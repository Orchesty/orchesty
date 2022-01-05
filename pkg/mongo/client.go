package mongo

import (
	"github.com/hanaboso/pipes/counter/pkg/config"
	"github.com/rs/zerolog/log"
	"go.mongodb.org/mongo-driver/mongo"
	"go.mongodb.org/mongo-driver/mongo/options"
	"strings"
)

type MongoDb struct {
	client     *mongo.Client
	database   *mongo.Database
	collection *mongo.Collection
}

func NewMongo() *MongoDb {
	dsn := config.MongoDb.Dsn
	client, err := mongo.NewClient(
		options.
			Client().
			ApplyURI(dsn).
			SetMaxPoolSize(10),
	)

	if err != nil {
		log.Fatal().Err(err).Send()
	}

	err = client.Connect(nil)
	if err != nil {
		log.Fatal().Err(err).Send()
	}

	parts := strings.Split(dsn, "/")
	parts = strings.Split(parts[len(parts)-1], "?")
	db := client.Database(parts[0])

	return &MongoDb{
		client:     client,
		database:   db,
		collection: db.Collection(config.MongoDb.CounterCollection),
	}
}

func (m *MongoDb) Close() {
	if err := m.client.Disconnect(nil); err != nil {
		log.Err(err).Send()
	}
}
