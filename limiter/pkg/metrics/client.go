package metrics

import (
	"time"

	"limiter/pkg/config"
	"limiter/pkg/mongo"

	innerMetrics "github.com/hanaboso/go-metrics/pkg"
	"github.com/rs/zerolog/log"
	"go.mongodb.org/mongo-driver/v2/bson"
	driver "go.mongodb.org/mongo-driver/v2/mongo"
	"go.mongodb.org/mongo-driver/v2/mongo/options"
)

type (
	MetricsSvc struct {
		mongo              mongo.MongoSvc
		metrics            innerMetrics.Interface
		limiterCollection  string
		userTaskCollection string
	}

	MetricsNode struct {
		Id struct {
			NodeId   string `bson:"nodeId"`
			NodeName string `bson:"nodeName"`
			UserId   string `bson:"userId"`
		} `bson:"_id"`
		Messages      int    `bson:"messages"`
		TopologyId    string `bson:"topologyId"`
		ApplicationId string `bson:"applicationId"`
	}
)

func NewMetricsSvc(mongoSvc mongo.MongoSvc) MetricsSvc {
	return MetricsSvc{
		mongo:              mongoSvc,
		metrics:            innerMetrics.Connect(config.MongoDb.MetricsDsn),
		limiterCollection:  config.MongoDb.MetricsLimiterCollection,
		userTaskCollection: config.MongoDb.MetricsUserTaskCollection,
	}
}

func (this MetricsSvc) Start() {
	go this.collectMetrics()
}

func (this MetricsSvc) Stop() {
	this.metrics.Disconnect()
}

func (this MetricsSvc) collectMetrics() {
	time.Sleep(time.Duration(60-time.Now().Second()) * time.Second)

	for range time.Tick(time.Minute) {
		this.collectFromCollection(
			this.mongo.Collection(),
			bson.A{
				bson.D{
					{
						"$group", bson.D{
							{"_id", bson.D{
								{"nodeId", "$message.headers.node-id"},
								{"nodeName", "$message.headers.node-name"},
								{"userId", "$message.headers.user"},
							}},
							{"messages", bson.D{{"$sum", 1}}},
							{"topologyId", bson.D{{"$first", "$message.headers.topology-id"}}},
							{"applicationId", bson.D{{"$first", "$message.headers.application"}}},
						},
					},
				},
			},
			bson.D{
				{"message.headers.node-id", 1},
				{"message.headers.node-name", 1},
				{"message.headers.user", 1},
				{"message.headers.topology-id", 1},
				{"message.headers.application", 1},
			},
			this.limiterCollection,
		)

		this.collectFromCollection(
			this.mongo.UserTaskCollection(),
			bson.A{
				bson.D{
					{
						"$match", bson.D{
							{
								"type", "trash",
							},
						},
					},
				},
				bson.D{
					{
						"$group", bson.D{
							{"_id", bson.D{
								{"nodeId", "$message.headers.node-id"},
								{"nodeName", "$message.headers.node-name"},
								{"userId", "$message.headers.user"},
							}},
							{"messages", bson.D{{"$sum", 1}}},
							{"topologyId", bson.D{{"$first", "$message.headers.topology-id"}}},
							{"applicationId", bson.D{{"$first", "$message.headers.application"}}},
						},
					},
				},
			},
			bson.D{
				{"type", 1},
				{"message.headers.node-id", 1},
				{"message.headers.node-name", 1},
				{"message.headers.user", 1},
				{"message.headers.topology-id", 1},
				{"message.headers.application", 1},
			},
			this.userTaskCollection,
		)
	}
}

func (this MetricsSvc) collectFromCollection(
	collection *driver.Collection,
	pipeline bson.A,
	hint bson.D,
	metricsCollection string,
) {
	go func() {
		ctx, _ := this.mongo.Connection().Context()
		cursor, err := collection.Aggregate(ctx, pipeline, options.Aggregate().SetHint(hint))

		if err != nil {
			log.Error().Err(err).Msg("Failed to query metrics")
			return
		}

		defer func() {
			_ = cursor.Close(ctx)
		}()

		if err = this.insertMetrics(cursor, metricsCollection); err != nil {
			log.Error().Err(err).Msg("Failed to iterate metrics")
		}
	}()
}

func (this MetricsSvc) insertMetrics(cursor *driver.Cursor, metricsCollection string) error {
	ctx, _ := this.mongo.Connection().Context()

	for cursor.Next(ctx) {
		var metricsNode MetricsNode

		if err := cursor.Decode(&metricsNode); err != nil {
			log.Error().Err(err).Msg("Failed to decode metrics")
			continue
		}

		if err := this.metrics.Send(metricsCollection, map[string]interface{}{
			"userId":        metricsNode.Id.UserId,
			"nodeId":        metricsNode.Id.NodeId,
			"nodeName":      metricsNode.Id.NodeName,
			"topologyId":    metricsNode.TopologyId,
			"applicationId": metricsNode.ApplicationId,
		}, map[string]interface{}{
			"created":  time.Now(),
			"messages": metricsNode.Messages,
		}); err != nil {
			log.Error().Err(err).Msg("Failed to send metrics")
		}
	}

	return cursor.Err()
}
