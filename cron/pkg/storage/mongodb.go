package storage

import (
	"github.com/hanaboso/go-mongodb"
	"go.mongodb.org/mongo-driver/bson"
	"go.mongodb.org/mongo-driver/mongo"
	"go.mongodb.org/mongo-driver/mongo/options"

	"cron/pkg/model"

	log "github.com/hanaboso/go-log/pkg"
)

type MongoStorage struct {
	connection         *mongodb.Connection
	collection         *mongo.Collection
	apiTokenCollection *mongo.Collection
	logger             log.Logger
}

func NewStorage(connection *mongodb.Connection, logger log.Logger, collection string, apiTokenCollection string) MongoStorage {
	service := MongoStorage{connection, connection.Database.Collection(collection), connection.Database.Collection(apiTokenCollection), logger}

	if err := service.createIndex(mongo.IndexModel{
		Keys:    []bson.E{{model.Topology, 1}, {model.Node, 1}},
		Options: options.Index().SetUnique(true),
	}); err != nil {
		service.logContext().Error(err)

		panic(err)
	}

	return service
}

func (storage MongoStorage) InsertApiToken(user string, scopes []string, key string) {
	context, cancel := storage.connection.Context()
	defer cancel()

	_, err := storage.apiTokenCollection.InsertOne(context, map[string]interface{}{"user": user, "scopes": scopes, "key": key})

	if err != nil {
		storage.logContext().Error(err)
	}
}

func (storage MongoStorage) DropApiTokenCollection() {
	context, cancel := storage.connection.Context()
	defer cancel()

	err := storage.apiTokenCollection.Drop(context)

	if err != nil {
		storage.logContext().Error(err)
	}
}

func (storage MongoStorage) FindApiToken(user string, scopes []string) (*model.ApiToken, error) {
	context, cancel := storage.connection.Context()
	defer cancel()

	var apiToken model.ApiToken
	err := storage.apiTokenCollection.FindOne(context, map[string]interface{}{"user": user, "scopes": scopes}).Decode(&apiToken)

	if err != nil {
		if err.Error() != "mongo: no documents in result" {
			storage.logContext().Error(err)
		}

		return nil, err
	}

	return &apiToken, err
}

func (storage MongoStorage) FindCrons() ([]model.Cron, error) {
	context, cancel := storage.connection.Context()
	defer cancel()

	var cron model.Cron
	var crons []model.Cron

	cursor, err := storage.collection.Find(context, map[string]interface{}{})

	if err != nil {
		storage.logContext().Error(err)

		return crons, err
	}

	defer func() {
		if err = cursor.Close(context); err != nil {
			storage.logContext().Error(err)
		}
	}()

	for cursor.Next(context) {
		err = cursor.Decode(&cron)

		if err != nil {
			storage.logContext().Error(err)

			return crons, err
		}

		crons = append(crons, cron)
	}

	return crons, nil
}

func (storage MongoStorage) UpsertCron(crons []model.Cron) error {
	context, cancel := storage.connection.Context()
	defer cancel()

	for _, cron := range crons {
		if _, err := storage.collection.UpdateOne(context, map[string]interface{}{
			model.Topology: cron.Topology,
			model.Node:     cron.Node,
		}, storage.createUpsert(map[string]interface{}{
			model.Time:       cron.Time,
			model.Parameters: cron.Parameters,
		}), options.Update().SetUpsert(true)); err != nil {
			storage.logContext().Error(err)

			return err
		}
	}

	return nil
}

func (storage MongoStorage) DeleteCron(crons []model.Cron) error {
	context, cancel := storage.connection.Context()
	defer cancel()

	if _, err := storage.collection.DeleteMany(context, storage.createDelete(crons)); err != nil {
		storage.logContext().Error(err)

		return err
	}

	return nil
}

func (storage MongoStorage) createUpsert(data map[string]interface{}) map[string]interface{} {
	return map[string]interface{}{"$set": data}
}

func (storage MongoStorage) createDelete(crons []model.Cron) map[string]interface{} {
	var ors []interface{}

	for _, cron := range crons {
		ors = append(ors, map[string]interface{}{"$and": []map[string]interface{}{{
			model.Topology: cron.Topology,
			model.Node:     cron.Node,
		}}})
	}

	return map[string]interface{}{"$or": ors}
}

func (storage MongoStorage) createIndex(indexModel mongo.IndexModel) error {
	context, cancel := storage.connection.Context()
	defer cancel()

	_, err := storage.collection.Indexes().CreateOne(context, indexModel)

	return err
}

func (storage MongoStorage) logContext() log.Logger {
	return storage.logger.WithFields(map[string]interface{}{
		"service": "MONGO",
		"type":    "Storage",
	})
}
