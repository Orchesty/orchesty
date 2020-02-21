package services

import (
	"context"
	"time"

	"github.com/hanaboso/go-mongodb"
	"go.mongodb.org/mongo-driver/bson"
	"go.mongodb.org/mongo-driver/mongo"
	"rabbitmq-telegraf/pkg/config"

	log "github.com/sirupsen/logrus"
)

type MongoDbSender struct {
	mongo      *mongodb.Connection
	cl         *mongo.Client
	dbname     string
	collection string
}

func (db *MongoDbSender) Send(queues []Queue) error {
	for _, q := range queues {
		if q.Messages <= 0 {
			continue
		}
		log.Debugf("logging: %+v", q)

		ctx, _ := context.WithTimeout(context.Background(), 5*time.Second)
		if _, err := db.mongo.Database.Collection(config.MongoDb.Collection).InsertOne(ctx, bson.M{
			"tags": bson.M{
				"queue": q.Name,
			},
			"fields": bson.M{
				"messages": q.Messages,
				"created":  time.Now().Unix(),
			},
		}); err != nil {
			log.Debugf("error at sending: %s", err)
			return err
		}
	}

	return nil
}

func NewMongoDbSenderSvc() SenderSvc {
	log.Infof("Connecting to MongoDB: %s", config.MongoDb.DSN)
	connection := &mongodb.Connection{}
	connection.Connect(config.MongoDb.DSN)
	log.Info("MongoDB successfully connected!")

	return &MongoDbSender{
		mongo: connection,
	}
}
