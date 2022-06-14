package mongo

import (
	"context"
	"github.com/hanaboso/pipes/counter/pkg/model"
	"github.com/rs/zerolog/log"
	"go.mongodb.org/mongo-driver/bson"
	"go.mongodb.org/mongo-driver/bson/bsontype"
	"go.mongodb.org/mongo-driver/bson/primitive"
	"go.mongodb.org/mongo-driver/mongo"
	"time"
)

func (m *MongoDb) LoadProcess(correlationId string) *model.Process {
	for {
		ctx, cancel := context.WithTimeout(context.Background(), 30*time.Second)
		result := m.collection.FindOne(ctx, bson.M{
			"correlationId": correlationId,
		})
		err := result.Err()
		if err != nil {
			cancel()
			return nil
		}

		var process model.ProcessWithId
		if err = result.Decode(&process); err != nil {
			log.Error().Err(err).Send()
			time.Sleep(time.Second)
			cancel()
			continue
		}

		cancel()
		p := process.IntoProcess()
		return &p
	}
}

func (m *MongoDb) LoadProcesses() model.Processes {
	for {
		ctx, cancel := context.WithTimeout(context.Background(), 30*time.Second)
		cursor, err := m.collection.Find(ctx, bson.M{
			"finished": bsontype.Null,
		})
		if err != nil {
			log.Error().Err(err).Send()
			time.Sleep(time.Second)
			cancel()
			continue
		}

		var processes []model.ProcessWithId
		if err = cursor.All(ctx, &processes); err != nil {
			log.Error().Err(err).Send()
			time.Sleep(time.Second)
			cancel()
			continue
		}

		mapped := make(model.Processes)
		for _, process := range processes {
			p := process.IntoProcess()
			mapped[process.CorrelationId] = &p
		}

		cancel()
		return mapped
	}
}

func (m *MongoDb) UpdateProcesses(processes model.Processes) {
	sess, err := m.connection.StartSession()
	if err != nil {
		log.Fatal().Err(err).Send()
	}

	ctx, _ := context.WithTimeout(context.Background(), 30*time.Second)
	defer func() {
		sess.EndSession(ctx)
	}()

	err = sess.StartTransaction()
	if err != nil {
		log.Fatal().Err(err).Send()
	}

	err = mongo.WithSession(ctx, sess, func(sc mongo.SessionContext) error {
		var updates []mongo.WriteModel
		var inserts []interface{}
		var insertIds []string
		var toDelete []string

		for key, process := range processes {
			if process.Id.IsZero() {
				inserts = append(inserts, process)
				insertIds = append(insertIds, process.CorrelationId)
			} else {
				operation := mongo.NewUpdateOneModel()
				operation.Filter = bson.M{"_id": process.Id}
				operation.Update = bson.M{
					"$set": process,
				}
				updates = append(updates, operation)

				if process.IsFinished() || !process.IsActive() {
					toDelete = append(toDelete, key)
				}
			}
		}

		if len(updates) > 0 {
			_, err := m.collection.BulkWrite(ctx, updates)
			if err != nil {
				_ = sess.AbortTransaction(ctx)
				return err
			}
		}

		if len(insertIds) > 0 {
			res, err := m.collection.InsertMany(ctx, inserts)
			if err != nil {
				_ = sess.AbortTransaction(ctx)
				return err
			}

			for index, id := range res.InsertedIDs {
				processes[insertIds[index]].Id = id.(primitive.ObjectID)
			}
		}

		if err = sess.CommitTransaction(sc); err != nil {
			return err
		}
		for _, key := range toDelete {
			delete(processes, key)
		}

		return nil
	})

	if err != nil {
		log.Fatal().Err(err).Send()
	}
}
