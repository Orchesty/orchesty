package mongo

import (
	"context"
	"github.com/hanaboso/pipes/counter/pkg/config"
	"github.com/rs/zerolog/log"
	"go.mongodb.org/mongo-driver/bson"
	"go.mongodb.org/mongo-driver/mongo"
	"go.mongodb.org/mongo-driver/mongo/options"
	"strings"
	"time"
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

	coll := db.Collection(config.MongoDb.CounterCollection)

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
	ctx, cancel := context.WithTimeout(context.Background(), 30*time.Second)
	if _, err := coll.Indexes().CreateMany(ctx, []mongo.IndexModel{indexCorr, indexFinished, indexExpires}); err != nil {
		log.Err(err).Send()
	}
	cancel()

	return &MongoDb{
		client:     client,
		database:   db,
		collection: coll,
	}
}

func (m *MongoDb) Close() {
	if err := m.client.Disconnect(nil); err != nil {
		log.Err(err).Send()
	}
}
