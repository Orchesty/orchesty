package metrics

import (
	"fmt"
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
		repeaterCollection string
		userTaskCollection string
	}

	MetricsNode struct {
		Id struct {
			NodeId   string `bson:"nodeId"`
			NodeName string `bson:"nodeName"`
			UserId   string `bson:"userId"`
		} `bson:"_id"`
		Messages      int    `bson:"messages"`
		Incoming      int    `bson:"incoming"`
		TopologyId    string `bson:"topologyId"`
		ApplicationId string `bson:"applicationId"`
	}
)

func NewMetricsSvc(mongoSvc mongo.MongoSvc) MetricsSvc {
	return MetricsSvc{
		mongo:              mongoSvc,
		metrics:            innerMetrics.Connect(config.MongoDb.MetricsDsn),
		limiterCollection:  config.MongoDb.MetricsLimiterCollection,
		repeaterCollection: config.MongoDb.MetricsRepeaterCollection,
		userTaskCollection: config.MongoDb.MetricsUserTaskCollection,
	}
}

func (this MetricsSvc) Start() {
	go this.collectMetrics()
}

func (this MetricsSvc) Stop() {
	this.metrics.Disconnect()
}

func buildGroupStage(lastTick time.Time) bson.D {
	return bson.D{
		{
			"$group", bson.D{
			{"_id", bson.D{
				{"nodeId", "$message.headers.node-id"},
				{"nodeName", "$message.headers.node-name"},
				{"userId", "$message.headers.user"},
			}},
			{"messages", bson.D{{"$sum", 1}}},
			{"incoming", bson.D{{"$sum", bson.D{{"$cond", bson.A{
				bson.D{{"$gte", bson.A{"$created", lastTick}}},
				1, 0,
			}}}}}},
			{"topologyId", bson.D{{"$first", "$message.headers.topology-id"}}},
			{"applicationId", bson.D{{"$first", "$message.headers.application"}}},
		},
		},
	}
}

func (this MetricsSvc) collectMetrics() {
	time.Sleep(time.Until(time.Now().Truncate(time.Minute).Add(time.Minute)))

	limiterAndRepeaterHint := bson.D{
		{"prioritize", 1},
		{"created", 1},
		{"message.headers.node-id", 1},
		{"message.headers.node-name", 1},
		{"message.headers.user", 1},
		{"message.headers.topology-id", 1},
		{"message.headers.application", 1},
	}

	trashHint := bson.D{
		{"type", 1},
		{"created", 1},
		{"message.headers.node-id", 1},
		{"message.headers.node-name", 1},
		{"message.headers.user", 1},
		{"message.headers.topology-id", 1},
		{"message.headers.application", 1},
	}

	lastTick := time.Now()
	previousLimiterCounts := make(map[string]int)
	previousRepeaterCounts := make(map[string]int)
	previousTrashCounts := make(map[string]int)

	for range time.Tick(time.Minute) {
		tickStart := time.Now()
		groupStage := buildGroupStage(lastTick)

		previousLimiterCounts = this.collectWithFlowMetrics(
			this.mongo.Collection(),
			bson.A{
				bson.D{{"$match", bson.D{{"prioritize", false}}}},
				groupStage,
			},
			limiterAndRepeaterHint,
			this.limiterCollection,
			previousLimiterCounts,
		)

		previousRepeaterCounts = this.collectWithFlowMetrics(
			this.mongo.Collection(),
			bson.A{
				bson.D{{"$match", bson.D{{"prioritize", true}}}},
				groupStage,
			},
			limiterAndRepeaterHint,
			this.repeaterCollection,
			previousRepeaterCounts,
		)

		previousTrashCounts = this.collectWithFlowMetrics(
			this.mongo.UserTaskCollection(),
			bson.A{
				bson.D{{"$match", bson.D{{"type", "trash"}}}},
				groupStage,
			},
			trashHint,
			this.userTaskCollection,
			previousTrashCounts,
		)

		lastTick = tickStart
	}
}

func (this MetricsSvc) collectWithFlowMetrics(
	collection *driver.Collection,
	pipeline bson.A,
	hint bson.D,
	metricsCollection string,
	previousCounts map[string]int,
) map[string]int {
	ctx, _ := this.mongo.Connection().Context()
	cursor, err := collection.Aggregate(ctx, pipeline, options.Aggregate().SetHint(hint))

	if err != nil {
		log.Error().Err(err).Msg("Failed to query metrics")

		return previousCounts
	}

	defer func() {
		_ = cursor.Close(ctx)
	}()

	newCounts := make(map[string]int)

	for cursor.Next(ctx) {
		var metricsNode MetricsNode

		if err = cursor.Decode(&metricsNode); err != nil {
			log.Error().Err(err).Msg("Failed to decode metrics")

			continue
		}

		nodeKey := fmt.Sprintf("%s|%s|%s", metricsNode.Id.NodeId, metricsNode.Id.NodeName, metricsNode.Id.UserId)
		outgoing := previousCounts[nodeKey] + metricsNode.Incoming - metricsNode.Messages

		if outgoing < 0 {
			outgoing = 0
		}

		newCounts[nodeKey] = metricsNode.Messages

		if err = this.metrics.Send(metricsCollection, map[string]interface{}{
			"userId":        metricsNode.Id.UserId,
			"nodeId":        metricsNode.Id.NodeId,
			"nodeName":      metricsNode.Id.NodeName,
			"topologyId":    metricsNode.TopologyId,
			"applicationId": metricsNode.ApplicationId,
		}, map[string]interface{}{
			"created":  time.Now(),
			"messages": metricsNode.Messages,
			"incoming": metricsNode.Incoming,
			"outgoing": outgoing,
		}); err != nil {
			log.Error().Err(err).Msg("Failed to send metrics")
		}
	}

	if err = cursor.Err(); err != nil {
		log.Error().Err(err).Msg("Failed to iterate metrics")
	}

	return newCounts
}
