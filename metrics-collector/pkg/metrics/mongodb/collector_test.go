package mongodb

import (
	"context"
	"testing"
	"time"

	"metrics-collector/pkg/models"
)

type mongoRepoStub struct {
	monthlyMetrics []*models.MongoDBMetric
	savedAgg       *models.MongoAggregation
	saveCalls      int
}

func (r *mongoRepoStub) SaveRabbitMQMetric(context.Context, *models.RabbitMQMetric) error { return nil }
func (r *mongoRepoStub) SaveMongoDBMetric(context.Context, *models.MongoDBMetric) error   { return nil }
func (r *mongoRepoStub) SaveK8sMetric(context.Context, *models.K8sMetric) error           { return nil }
func (r *mongoRepoStub) SaveLokiMetric(context.Context, *models.LokiMetric) error         { return nil }

func (r *mongoRepoStub) SaveRabbitAggregation(context.Context, *models.RabbitAggregation) error {
	return nil
}

func (r *mongoRepoStub) SaveMongoAggregation(_ context.Context, agg *models.MongoAggregation) error {
	r.saveCalls++
	r.savedAgg = agg
	return nil
}

func (r *mongoRepoStub) SaveK8sAggregation(context.Context, *models.K8sAggregation) error { return nil }
func (r *mongoRepoStub) SaveLokiAggregation(context.Context, *models.LokiAggregation) error {
	return nil
}

func (r *mongoRepoStub) GetRabbitMQMetricsForMonth(context.Context) ([]*models.RabbitMQMetric, error) {
	return nil, nil
}
func (r *mongoRepoStub) GetMongoDBMetricsForMonth(context.Context) ([]*models.MongoDBMetric, error) {
	return r.monthlyMetrics, nil
}
func (r *mongoRepoStub) GetK8sMetricsForMonth(context.Context) ([]*models.K8sMetric, error) {
	return nil, nil
}
func (r *mongoRepoStub) GetLokiMetricsForMonth(context.Context) ([]*models.LokiMetric, error) {
	return nil, nil
}

func TestAggregateMetrics_SavesExpectedAggregation(t *testing.T) {
	repo := &mongoRepoStub{
		monthlyMetrics: []*models.MongoDBMetric{
			{TotalDocuments: 100, DataSizeMB: 10, StorageSizeMB: 50},
			{TotalDocuments: 200, DataSizeMB: 20, StorageSizeMB: 70},
		},
	}

	collector := &Collector{}
	err := collector.aggregateMetrics(context.Background(), repo)
	if err != nil {
		t.Fatalf("unexpected error: %v", err)
	}

	if repo.saveCalls != 1 {
		t.Fatalf("expected one save call, got %d", repo.saveCalls)
	}

	if repo.savedAgg == nil {
		t.Fatal("expected saved aggregation")
	}

	if repo.savedAgg.AvgDataSizeMB != 15 {
		t.Fatalf("expected AvgDataSizeMB=15, got %v", repo.savedAgg.AvgDataSizeMB)
	}
	if repo.savedAgg.MaxDataSizeMB != 20 {
		t.Fatalf("expected MaxDataSizeMB=20, got %v", repo.savedAgg.MaxDataSizeMB)
	}
	if repo.savedAgg.AvgStorageSizeMB != 60 {
		t.Fatalf("expected AvgStorageSizeMB=60, got %v", repo.savedAgg.AvgStorageSizeMB)
	}
	if repo.savedAgg.MaxStorageSizeMB != 70 {
		t.Fatalf("expected MaxStorageSizeMB=70, got %v", repo.savedAgg.MaxStorageSizeMB)
	}
	if repo.savedAgg.AvgDocuments != 150 {
		t.Fatalf("expected AvgDocuments=150, got %v", repo.savedAgg.AvgDocuments)
	}
	if repo.savedAgg.Month != time.Now().Format("2006-01") {
		t.Fatalf("unexpected month %q", repo.savedAgg.Month)
	}
}

func TestAggregateMetrics_NoMetrics(t *testing.T) {
	repo := &mongoRepoStub{monthlyMetrics: []*models.MongoDBMetric{}}
	collector := &Collector{}

	err := collector.aggregateMetrics(context.Background(), repo)
	if err != nil {
		t.Fatalf("unexpected error: %v", err)
	}

	if repo.saveCalls != 0 {
		t.Fatalf("expected zero save calls, got %d", repo.saveCalls)
	}
}
