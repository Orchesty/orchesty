package storage

import (
	"github.com/hanaboso/go-mongodb"
	"go.mongodb.org/mongo-driver/bson"
	"go.mongodb.org/mongo-driver/mongo"
	"go.mongodb.org/mongo-driver/mongo/options"

	"cron/pkg/model"

	log "github.com/hanaboso/go-log/pkg"
)

type (
	CronStorage interface {
		Select() ([]model.Cron, error)
		Upsert(crons []model.Cron) error
		Delete(crons []model.Cron) error
	}

	cronStorage struct {
		connection *mongodb.Connection
		collection *mongo.Collection
		logger     log.Logger
	}
)

func NewCronStorage(connection *mongodb.Connection, logger log.Logger, collection string) CronStorage {
	service := cronStorage{connection, connection.Database.Collection(collection), logger}

	if err := service.createIndex(mongo.IndexModel{
		Keys:    []bson.E{{model.Topology, 1}, {model.Node, 1}},
		Options: options.Index().SetUnique(true),
	}); err != nil {
		service.logContext().Error(err)

		panic(err)
	}

	return service
}

func (storage cronStorage) Select() ([]model.Cron, error) {
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

func (storage cronStorage) Upsert(crons []model.Cron) error {
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

func (storage cronStorage) Delete(crons []model.Cron) error {
	context, cancel := storage.connection.Context()
	defer cancel()

	if _, err := storage.collection.DeleteMany(context, storage.createDelete(crons)); err != nil {
		storage.logContext().Error(err)

		return err
	}

	return nil
}

func (storage cronStorage) createUpsert(data map[string]interface{}) map[string]interface{} {
	return map[string]interface{}{"$set": data}
}

func (storage cronStorage) createDelete(crons []model.Cron) map[string]interface{} {
	var ors []interface{}

	for _, cron := range crons {
		ors = append(ors, map[string]interface{}{"$and": []map[string]interface{}{{
			model.Topology: cron.Topology,
			model.Node:     cron.Node,
		}}})
	}

	return map[string]interface{}{"$or": ors}
}

func (storage cronStorage) createIndex(indexModel mongo.IndexModel) error {
	context, cancel := storage.connection.Context()
	defer cancel()

	_, err := storage.collection.Indexes().CreateOne(context, indexModel)

	return err
}

func (storage cronStorage) logContext() log.Logger {
	return storage.logger.WithFields(map[string]interface{}{
		"service": "CRON",
		"type":    "Storage",
	})
}
