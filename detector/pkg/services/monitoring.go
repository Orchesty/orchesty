package services

import (
	"detector/pkg/config"
	"fmt"
	"github.com/hanaboso/go-mongodb"
	"go.mongodb.org/mongo-driver/bson"
	"go.mongodb.org/mongo-driver/bson/primitive"
	"go.mongodb.org/mongo-driver/mongo"
	"time"
)

type Monitoring struct {
	ProcsInProgress        int
	LimiterCount           int
	RepeaterCount          int
	OperationSum           int32
	ProcsFailed            int32
	ProcsSucceeded         int32
	LastT                  time.Time
	multiCounterCollection *mongo.Collection
	limiterCollection      *mongo.Collection
	repeaterCollection     *mongo.Collection
	mongo                  *mongodb.Connection
}

func (m *Monitoring) Run() {
	for range time.Tick(config.App.Tick) {
		context, _ := m.mongo.Context()
		now := time.Now()

		inProgress, err := m.multiCounterCollection.CountDocuments(context, map[string]interface{}{"finished": primitive.Null{}})

		if err != nil {
			config.Logger.Fatal(err)
		}

		limiterCount, err := m.limiterCollection.CountDocuments(context, bson.M{
			"allowedAt": bson.M{"$eq": "$created"},
		})

		if err != nil {
			config.Logger.Fatal(err)
		}

		repeaterCount, err := m.repeaterCollection.CountDocuments(context, bson.M{
			"allowedAt": bson.M{"$ne": "$created"},
		})

		if err != nil {
			config.Logger.Fatal(err)
		}

		pipeline := mongo.Pipeline{
			{
				{
					"$match", bson.D{
						{
							"finished", bson.D{
								{"$gt", primitive.NewDateTimeFromTime(m.LastT)},
								{"$lte", primitive.NewDateTimeFromTime(now)},
							},
						},
					},
				},
			},
			{
				{"$group", bson.D{
					{"_id", primitive.Null{}},
					{
						"succeeded", bson.D{
							{
								"$sum", bson.D{
									{
										"$cond", bson.D{
											{"if", bson.D{{"$eq", bson.A{"$nok", 0}}}},
											{"then", 1},
											{"else", 0},
										},
									},
								},
							},
						},
					},
					{
						"failed", bson.D{
							{
								"$sum", bson.D{
									{
										"$cond", bson.D{
											{"if", bson.D{{"$gte", bson.A{"$nok", 1}}}},
											{"then", 1},
											{"else", 0},
										},
									},
								},
							},
						},
					},
					{
						"totalAmount", bson.D{
							{
								"$sum", "$total",
							},
						},
					},
				},
				},
			},
		}

		aggregation, err := m.multiCounterCollection.Aggregate(context, pipeline)

		var results []bson.M
		if err != nil {
			config.Logger.Fatal(err)
		}

		err = aggregation.All(context, &results)
		if err != nil {
			config.Logger.Fatal(err)
		}

		if len(results) == 0 {
			continue
		}

		m.ProcsInProgress = int(inProgress)
		m.LimiterCount = int(limiterCount)
		m.RepeaterCount = int(repeaterCount)
		m.OperationSum = m.OperationSum + results[0]["totalAmount"].(int32)
		m.ProcsFailed = m.ProcsFailed + results[0]["failed"].(int32)
		m.ProcsSucceeded = m.ProcsSucceeded + results[0]["succeeded"].(int32)

		m.LastT = now
	}
}

func (m *Monitoring) FormatResult() string {
	var response string

	response = fmt.Sprintf("procs_in_progress{version=\"1\"} %d\n", m.ProcsInProgress)
	response += fmt.Sprintf("limiter_count{version=\"1\"} %d\n", m.LimiterCount)
	response += fmt.Sprintf("repeater_count{version=\"1\"} %d\n", m.RepeaterCount)
	response += fmt.Sprintf("operation_sum{version=\"1\"} %d\n", m.OperationSum)
	response += fmt.Sprintf("procs_failed{version=\"1\"} %d\n", m.ProcsFailed)
	response += fmt.Sprintf("procs_succeeded{version=\"1\"} %d\n", m.ProcsSucceeded)

	return response
}

func NewMonitoring(mongo *mongodb.Connection, multiCounterCollection string, limiterCollection string, repeaterCollection string) Monitoring {
	return Monitoring{
		LastT:                  time.Now(),
		multiCounterCollection: mongo.Database.Collection(multiCounterCollection),
		limiterCollection:      mongo.Database.Collection(limiterCollection),
		repeaterCollection:     mongo.Database.Collection(repeaterCollection),
		mongo:                  mongo,
	}
}
