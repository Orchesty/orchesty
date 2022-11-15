package mongo

import (
	"context"
	"github.com/hanaboso/pipes/counter/pkg/config"
	"github.com/hanaboso/pipes/counter/pkg/model"
	"github.com/rs/zerolog/log"
	"go.mongodb.org/mongo-driver/bson"
	"go.mongodb.org/mongo-driver/mongo"
	"time"
)

func (m *MongoDb) GetApiToken(user string, scopes []string) (*model.ApiToken, error) {
	ctx, cancel := context.WithTimeout(context.Background(), 30*time.Second)

	var apiToken model.ApiToken
	err := m.connection.Database.
		Collection(config.MongoDb.ApiTokenCollection).
		FindOne(ctx, map[string]interface{}{"user": user, "scopes": scopes}).
		Decode(&apiToken)

	if err != nil {
		cancel()

		log.Fatal().Err(err).Msg("ApiToken with current user and scope not found!")
		return nil, err
	}

	cancel()
	return &apiToken, err
}

func (m *MongoDb) GetProcess(id string) (model.Process, error) {
	ctx, cancel := context.WithTimeout(context.Background(), 30*time.Second)
	result := m.connection.Database.Collection(config.MongoDb.CounterCollection).FindOne(ctx, bson.M{
		"_id": id,
	})
	err := result.Err()
	if err != nil {
		cancel()
		return model.Process{}, err
	}

	var process model.Process
	err = result.Decode(&process)

	cancel()
	return process, err
}

func (m *MongoDb) GetUnmarkedFinishedProcesses() ([]model.Process, error) {
	var processes []model.Process
	ctx, cancel := context.WithTimeout(context.Background(), 30*time.Second)
	result, err := m.connection.Database.Collection(config.MongoDb.CounterCollection).Aggregate(ctx, bson.A{
		bson.M{
			"$match": bson.M{
				"finished": nil,
			},
		},
		bson.M{
			"$addFields": bson.M{
				"done": bson.M{
					"$cond": bson.A{
						bson.M{
							"$eq": bson.A{
								bson.M{
									"$add": bson.A{"$ok", "$nok"},
								},
								"$total",
							},
						},
						true,
						false,
					},
				},
			},
		},
		bson.M{
			"$match": bson.M{
				"done": true,
			},
		},
		bson.M{
			"$unset": "done",
		},
	})

	err = result.All(ctx, &processes)

	cancel()
	return processes, err
}

func (m *MongoDb) UpdateProcesses(processes, subProcesses, finishes []mongo.WriteModel, errors []bson.M) (finished []model.Process) {
	sess, err := m.connection.StartSession()
	if err != nil {
		log.Fatal().Err(err).Send()
	}

	ctx, cancel := context.WithTimeout(context.Background(), 60*time.Second)
	defer func() {
		sess.EndSession(ctx)
		cancel()
	}()

	err = sess.StartTransaction()
	if err != nil {
		log.Fatal().Err(err).Send()
	}

	err = mongo.WithSession(ctx, sess, func(sc mongo.SessionContext) error {
		_, err := m.connection.Database.Collection(config.MongoDb.CounterCollection).BulkWrite(ctx, processes)
		if err != nil {
			_ = sess.AbortTransaction(ctx)
			return err
		}
		_, err = m.connection.Database.Collection(config.MongoDb.CounterSubCollection).BulkWrite(ctx, subProcesses)
		if err != nil {
			_ = sess.AbortTransaction(ctx)
			return err
		}

		finished, err = m.GetUnmarkedFinishedProcesses()
		if err != nil {
			_ = sess.AbortTransaction(ctx)
			return err
		}

		_, err = m.connection.Database.Collection(config.MongoDb.CounterCollection).BulkWrite(ctx, finishes)
		if err != nil {
			_ = sess.AbortTransaction(ctx)
			return err
		}

		for _, errMsg := range errors {
			_, err = m.connection.Database.Collection(config.MongoDb.CounterErrCollection).InsertOne(ctx, errMsg)
			if err != nil {
				_ = sess.AbortTransaction(ctx)
				return err
			}
		}

		return sess.CommitTransaction(sc)
	})

	if err != nil {
		log.Fatal().Err(err).Send()
	}

	return
}

func (m *MongoDb) FetchErrorMessages(correlationId string) ([]model.ErrorMessage, error) {
	var processes []model.ErrorMessage
	ctx, cancel := context.WithTimeout(context.Background(), 30*time.Second)
	result, err := m.connection.Database.Collection(config.MongoDb.CounterErrCollection).Find(ctx, bson.M{
		"correlationId": correlationId,
	})

	err = result.All(ctx, &processes)
	cancel()

	return processes, err
}
