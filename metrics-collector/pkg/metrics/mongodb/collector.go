package mongodb

import (
	"context"
	"fmt"
	"time"

	"metrics-collector/pkg/config"
	"metrics-collector/pkg/metrics"
	"metrics-collector/pkg/models"
	"metrics-collector/pkg/utils"

	"github.com/hanaboso/go-mongodb"
	"go.mongodb.org/mongo-driver/v2/bson"
	"go.mongodb.org/mongo-driver/v2/mongo"
)

const CollectorName = "MongoDB"

type Collector struct {
	db        *mongo.Database
	metricsDb *mongo.Database
}

func NewCollector() *Collector {
	mongoDbCon := &mongodb.Connection{}
	mongoDbCon.Connect(config.Mongo.DataDsn)

	mongoMetricsCon := &mongodb.Connection{}
	mongoMetricsCon.Connect(config.Mongo.MetricsDsn)

	return &Collector{db: mongoDbCon.Database, metricsDb: mongoMetricsCon.Database}
}

func (c *Collector) Name() string {
	return CollectorName
}

func (c *Collector) Collect(ctx context.Context, repo metrics.Repository) error {
	metric, err := c.fetchMetrics(ctx)
	if err != nil {
		config.Logger.ErrorWrap("failed to fetch MongoDB metrics", err)
		return err
	}

	if err := repo.SaveMongoDBMetric(ctx, metric); err != nil {
		config.Logger.ErrorWrap("failed to save MongoDB metric", err)
		return err
	}

	if err := c.aggregateMetrics(ctx, repo); err != nil {
		config.Logger.ErrorWrap("failed to aggregate MongoDB metrics", err)
	}

	config.Logger.Debug("MongoDB metrics collected", map[string]interface{}{
		"documents": metric.TotalDocuments,
		"data_size": metric.DataSizeMB,
	})

	return nil
}

func (c *Collector) fetchMetrics(ctx context.Context) (*models.MongoDBMetric, error) {
	var statsData bson.M
	if err := c.db.RunCommand(ctx, bson.M{"dbStats": 1}).Decode(&statsData); err != nil {
		return nil, fmt.Errorf("failed to get dbStats from data database: %w", err)
	}

	var statsMetrics bson.M
	if err := c.metricsDb.RunCommand(ctx, bson.M{"dbStats": 1}).Decode(&statsMetrics); err != nil {
		return nil, fmt.Errorf("failed to get dbStats from metrics database: %w", err)
	}

	dataSizeBytesData := statsData["dataSize"].(float64)
	storageSizeBytesData := statsData["storageSize"].(float64)
	objectsData := statsData["objects"].(int64)
	collectionsData := int(statsData["collections"].(int64))

	dataSizeBytesMetrics := statsMetrics["dataSize"].(float64)
	storageSizeBytesMetrics := statsMetrics["storageSize"].(float64)
	objectsMetrics := statsMetrics["objects"].(int64)
	collectionsMetrics := int(statsMetrics["collections"].(int64))

	totalDataSizeBytes := dataSizeBytesData + dataSizeBytesMetrics
	totalStorageSizeBytes := storageSizeBytesData + storageSizeBytesMetrics
	totalObjects := objectsData + objectsMetrics
	totalCollections := collectionsData + collectionsMetrics

	replicasCount := 1
	if config.Mongo.HaMode {
		replicasCount = 3
	}

	dataSize := totalDataSizeBytes / (1024 * 1024) * float64(replicasCount)
	storageSize := totalStorageSizeBytes / (1024 * 1024) * float64(replicasCount)

	return &models.MongoDBMetric{
		TotalDocuments:   totalObjects,
		DataSizeMB:       utils.RoundFloat(dataSize, 2),
		StorageSizeMB:    utils.RoundFloat(storageSize, 2),
		CollectionsCount: totalCollections,
		Timestamp:        time.Now(),
	}, nil
}

func (c *Collector) aggregateMetrics(ctx context.Context, repo metrics.Repository) error {
	agg, err := repo.GetMongoDBMonthlyAggregation(ctx)
	if err != nil {
		return fmt.Errorf("failed to aggregate metrics in MongoDB: %w", err)
	}
	if agg == nil {
		return nil
	}
	return repo.SaveMongoAggregation(ctx, agg)
}
