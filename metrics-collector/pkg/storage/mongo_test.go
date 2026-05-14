package storage

import (
	"context"
	"fmt"
	"os"
	"strings"
	"testing"
	"time"

	"metrics-collector/pkg/config"
	"metrics-collector/pkg/models"

	"go.mongodb.org/mongo-driver/v2/bson"
)

func setupTestRepository(t *testing.T) *MongoRepository {
	t.Helper()

	originalDsn := config.Mongo.MetricsDsn
	originalK8sEnabled := config.Kubernetes.Enabled
	originalLokiEnabled := config.Loki.Enabled

	dbName := fmt.Sprintf("k8smetrics_test_%d", time.Now().UnixNano())
	baseDsn := os.Getenv("METRICS_COLLECTOR_TEST_DSN_BASE")
	if baseDsn == "" {
		baseDsn = "mongodb://mongodb:27017"
	}
	config.Mongo.MetricsDsn = fmt.Sprintf("%s/%s", strings.TrimRight(baseDsn, "/"), dbName)

	config.Kubernetes.Enabled = true
	config.Loki.Enabled = true

	ctx, cancel := context.WithTimeout(context.Background(), 10*time.Second)
	defer cancel()

	repo, err := NewMongoRepository(ctx)
	if err != nil {
		config.Mongo.MetricsDsn = originalDsn
		config.Kubernetes.Enabled = originalK8sEnabled
		config.Loki.Enabled = originalLokiEnabled
		t.Skipf("integration test skipped, mongo is not reachable: %v", err)
	}

	t.Cleanup(func() {
		cleanupCtx, cleanupCancel := context.WithTimeout(context.Background(), 10*time.Second)
		defer cleanupCancel()

		_ = repo.GetDB().Drop(cleanupCtx)
		_ = repo.Close()

		config.Mongo.MetricsDsn = originalDsn
		config.Kubernetes.Enabled = originalK8sEnabled
		config.Loki.Enabled = originalLokiEnabled
	})

	return repo
}

func TestMongoRepository_SaveAndGetMongoDBMetricsForMonth(t *testing.T) {
	repo := setupTestRepository(t)
	ctx, cancel := context.WithTimeout(context.Background(), 10*time.Second)
	defer cancel()

	nowMetric := &models.MongoDBMetric{
		TotalDocuments:   200,
		DataSizeMB:       12.5,
		StorageSizeMB:    20.5,
		CollectionsCount: 8,
		Timestamp:        time.Now().UTC(),
	}
	oldMetric := &models.MongoDBMetric{
		TotalDocuments:   100,
		DataSizeMB:       6.5,
		StorageSizeMB:    11.5,
		CollectionsCount: 5,
		Timestamp:        time.Now().UTC().AddDate(0, -1, 0),
	}

	if err := repo.SaveMongoDBMetric(ctx, nowMetric); err != nil {
		t.Fatalf("save current month metric failed: %v", err)
	}
	if err := repo.SaveMongoDBMetric(ctx, oldMetric); err != nil {
		t.Fatalf("save previous month metric failed: %v", err)
	}

	metrics, err := repo.GetMongoDBMetricsForMonth(ctx)
	if err != nil {
		t.Fatalf("get metrics failed: %v", err)
	}

	if len(metrics) != 1 {
		t.Fatalf("expected exactly one metric for current month, got %d", len(metrics))
	}

	if metrics[0].TotalDocuments != nowMetric.TotalDocuments {
		t.Fatalf("expected TotalDocuments=%d, got %d", nowMetric.TotalDocuments, metrics[0].TotalDocuments)
	}
}

func TestMongoRepository_SaveAndGetRabbitMQMetricsForMonth(t *testing.T) {
	repo := setupTestRepository(t)
	ctx, cancel := context.WithTimeout(context.Background(), 10*time.Second)
	defer cancel()

	nowMetric := &models.RabbitMQMetric{
		TotalMessages: 25,
		TotalDiskMB:   30.1,
		TotalRamMB:    5.7,
		Timestamp:     time.Now().UTC(),
	}
	oldMetric := &models.RabbitMQMetric{
		TotalMessages: 99,
		TotalDiskMB:   10,
		TotalRamMB:    2,
		Timestamp:     time.Now().UTC().AddDate(0, -1, 0),
	}

	if err := repo.SaveRabbitMQMetric(ctx, nowMetric); err != nil {
		t.Fatalf("save current month metric failed: %v", err)
	}
	if err := repo.SaveRabbitMQMetric(ctx, oldMetric); err != nil {
		t.Fatalf("save previous month metric failed: %v", err)
	}

	metrics, err := repo.GetRabbitMQMetricsForMonth(ctx)
	if err != nil {
		t.Fatalf("get metrics failed: %v", err)
	}

	if len(metrics) != 1 {
		t.Fatalf("expected exactly one metric for current month, got %d", len(metrics))
	}

	if metrics[0].TotalMessages != nowMetric.TotalMessages {
		t.Fatalf("expected TotalMessages=%d, got %d", nowMetric.TotalMessages, metrics[0].TotalMessages)
	}
}

func TestMongoRepository_SaveMongoAggregation_Upsert(t *testing.T) {
	repo := setupTestRepository(t)
	ctx, cancel := context.WithTimeout(context.Background(), 10*time.Second)
	defer cancel()

	month := time.Now().UTC().Format("2006-01")

	first := &models.MongoAggregation{
		Month:            month,
		AvgDataSizeMB:    10,
		MaxDataSizeMB:    12,
		AvgStorageSizeMB: 20,
		MaxStorageSizeMB: 24,
		AvgDocuments:     100,
		LastUpdated:      time.Now().UTC(),
	}
	second := &models.MongoAggregation{
		Month:            month,
		AvgDataSizeMB:    33,
		MaxDataSizeMB:    40,
		AvgStorageSizeMB: 55,
		MaxStorageSizeMB: 66,
		AvgDocuments:     777,
		LastUpdated:      time.Now().UTC(),
	}

	if err := repo.SaveMongoAggregation(ctx, first); err != nil {
		t.Fatalf("save first aggregation failed: %v", err)
	}
	if err := repo.SaveMongoAggregation(ctx, second); err != nil {
		t.Fatalf("save second aggregation failed: %v", err)
	}

	coll := repo.GetDB().Collection(CollectionMonthlyStorageAggregates)
	count, err := coll.CountDocuments(ctx, bson.M{FieldMonth: month})
	if err != nil {
		t.Fatalf("count documents failed: %v", err)
	}
	if count != 1 {
		t.Fatalf("expected 1 upserted document, got %d", count)
	}

	var saved models.MongoAggregation
	if err := coll.FindOne(ctx, bson.M{FieldMonth: month}).Decode(&saved); err != nil {
		t.Fatalf("find upserted aggregation failed: %v", err)
	}

	if saved.AvgDataSizeMB != second.AvgDataSizeMB {
		t.Fatalf("expected AvgDataSizeMB=%v, got %v", second.AvgDataSizeMB, saved.AvgDataSizeMB)
	}
	if saved.AvgDocuments != second.AvgDocuments {
		t.Fatalf("expected AvgDocuments=%v, got %v", second.AvgDocuments, saved.AvgDocuments)
	}
}
