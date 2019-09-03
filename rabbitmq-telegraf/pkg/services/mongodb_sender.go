package services

import (
	"context"
	"fmt"
	log "github.com/sirupsen/logrus"
	"go.mongodb.org/mongo-driver/bson"
	"go.mongodb.org/mongo-driver/mongo"
	"go.mongodb.org/mongo-driver/mongo/options"
	"rabbitmq-telegraf/pkg/config"
	"time"
)

type MongoDbSender struct {
	cl         *mongo.Client
	dbname     string
	collection string
}

func (db *MongoDbSender) Send(queues []Queue) error {
	if err := db.ensureConnected(); err != nil {
		return err
	}

	d := db.cl.Database(db.dbname, nil)
	if d == nil {
		return fmt.Errorf("database [name=%s] not found", db.dbname)
	}

	coll := d.Collection(db.collection)
	if coll == nil {
		return fmt.Errorf("collection [name=%s] not found", db.collection)
	}

	for _, q := range queues {
		if q.Messages <= 0 {
			continue
		}
		log.Debugf("logging: %+v", q)

		ctx, _ := context.WithTimeout(context.Background(), 5*time.Second)
		if _, err := coll.InsertOne(ctx, bson.M{
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

func (db *MongoDbSender) ensureConnected() error {
	ctx, _ := context.WithTimeout(context.Background(), 5*time.Second)
	if err := db.cl.Ping(ctx, nil); err == nil {
		return nil
	}

	maxTries := config.MongoDb.MaxReconnectTries

	var err error
	for c := 0; c < maxTries; c++ {
		ctx, _ = context.WithTimeout(context.Background(), 500*time.Second)
		err = db.cl.Connect(ctx)
		if err != nil {
			if c > maxTries {
				return err
			}

			log.Debugf("waiting 3 secs to re-try mongo connect: %s", err)
			time.Sleep(3 * time.Second)
		} else {
			break
		}
	}

	return nil
}

func NewMongoDbSenderSvc() SenderSvc {
	cl, err := mongo.NewClient(options.Client().ApplyURI(config.MongoDb.DSN))
	if err != nil {
		log.Fatal(err)
	}

	return &MongoDbSender{
		cl:         cl,
		dbname:     config.MongoDb.Database,
		collection: config.MongoDb.Collection,
	}
}
