package mongodb

import (
	"context"
	"fmt"
	"time"

	"metrics-collector/pkg/config"
	"metrics-collector/pkg/models"
	"metrics-collector/pkg/storage"
	"metrics-collector/pkg/utils"

	"go.mongodb.org/mongo-driver/v2/bson"
	"go.mongodb.org/mongo-driver/v2/mongo"
)

type Collector struct {
	db *mongo.Database
}

func NewCollector(db *mongo.Database) *Collector {
	return &Collector{db: db}
}

func (c *Collector) Name() string {
	return "MongoDB"
}

func (c *Collector) Collect(ctx context.Context, repo *storage.MongoRepository) error {
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
	var stats bson.M
	if err := c.db.RunCommand(ctx, bson.M{"dbStats": 1}).Decode(&stats); err != nil {
		return nil, fmt.Errorf("failed to get dbStats: %w", err)
	}

	dataSizeBytes := stats["dataSize"].(float64)
	storageSizeBytes := stats["storageSize"].(float64)
	objects := stats["objects"].(int64)
	collections := int(stats["collections"].(int64))

	return &models.MongoDBMetric{
		TotalDocuments:   objects,
		DataSizeMB:       utils.RoundFloat(dataSizeBytes/(1024*1024), 2),
		StorageSizeMB:    utils.RoundFloat(storageSizeBytes/(1024*1024), 2),
		CollectionsCount: collections,
		Timestamp:        time.Now(),
	}, nil
}

func (c *Collector) aggregateMetrics(ctx context.Context, repo *storage.MongoRepository) error {
	now := time.Now()
	metrics, err := repo.GetMongoDBMetricsForMonth(ctx)
	if err != nil {
		return fmt.Errorf("failed to get metrics for month: %w", err)
	}

	if len(metrics) == 0 {
		return nil
	}

	var sumDataSize, sumStorageSize, sumDocuments float64
	var maxDataSize, maxStorageSize float64

	for _, m := range metrics {
		sumDataSize += m.DataSizeMB
		sumStorageSize += m.StorageSizeMB
		sumDocuments += float64(m.TotalDocuments)

		if m.DataSizeMB > maxDataSize {
			maxDataSize = m.DataSizeMB
		}
		if m.StorageSizeMB > maxStorageSize {
			maxStorageSize = m.StorageSizeMB
		}
	}

	count := float64(len(metrics))

	currentMonth := now.Format("2006-01")

	agg := &models.MongoAggregation{
		Month:            currentMonth,
		AvgDataSizeMB:    utils.RoundFloat(sumDataSize/count, 2),
		MaxDataSizeMB:    utils.RoundFloat(maxDataSize, 2),
		AvgStorageSizeMB: utils.RoundFloat(sumStorageSize/count, 2),
		MaxStorageSizeMB: utils.RoundFloat(maxStorageSize, 2),
		AvgDocuments:     utils.RoundFloat(sumDocuments/count, 0),
		LastUpdated:      now,
	}

	return repo.SaveMongoAggregation(ctx, agg)
}
