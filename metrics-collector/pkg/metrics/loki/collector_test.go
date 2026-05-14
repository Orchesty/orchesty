package loki

import (
	"context"
	"encoding/json"
	"net/http"
	"net/http/httptest"
	"testing"
	"time"

	"metrics-collector/pkg/config"
	"metrics-collector/pkg/models"
)

type lokiRepoStub struct {
	monthlyMetrics []*models.LokiMetric
	savedAgg       *models.LokiAggregation
	saveCalls      int
}

func (r *lokiRepoStub) SaveRabbitMQMetric(context.Context, *models.RabbitMQMetric) error { return nil }
func (r *lokiRepoStub) SaveMongoDBMetric(context.Context, *models.MongoDBMetric) error   { return nil }
func (r *lokiRepoStub) SaveK8sMetric(context.Context, *models.K8sMetric) error           { return nil }
func (r *lokiRepoStub) SaveLokiMetric(context.Context, *models.LokiMetric) error         { return nil }

func (r *lokiRepoStub) SaveRabbitAggregation(context.Context, *models.RabbitAggregation) error {
	return nil
}
func (r *lokiRepoStub) SaveMongoAggregation(context.Context, *models.MongoAggregation) error {
	return nil
}
func (r *lokiRepoStub) SaveK8sAggregation(context.Context, *models.K8sAggregation) error { return nil }
func (r *lokiRepoStub) SaveLokiAggregation(_ context.Context, agg *models.LokiAggregation) error {
	r.saveCalls++
	r.savedAgg = agg
	return nil
}

func (r *lokiRepoStub) GetRabbitMQMetricsForMonth(context.Context) ([]*models.RabbitMQMetric, error) {
	return nil, nil
}
func (r *lokiRepoStub) GetMongoDBMetricsForMonth(context.Context) ([]*models.MongoDBMetric, error) {
	return nil, nil
}
func (r *lokiRepoStub) GetK8sMetricsForMonth(context.Context) ([]*models.K8sMetric, error) {
	return nil, nil
}
func (r *lokiRepoStub) GetLokiMetricsForMonth(context.Context) ([]*models.LokiMetric, error) {
	return r.monthlyMetrics, nil
}

func TestFetchMetrics_EmptyRangeResult(t *testing.T) {
	nowBeforeCall := time.Now()

	srv := httptest.NewServer(http.HandlerFunc(func(w http.ResponseWriter, r *http.Request) {
		switch r.URL.Path {
		case "/loki/api/v1/query_range":
			_ = json.NewEncoder(w).Encode(map[string]interface{}{
				"status": "success",
				"data": map[string]interface{}{
					"result": []interface{}{},
				},
			})
		case "/loki/api/v1/query":
			_ = json.NewEncoder(w).Encode(map[string]interface{}{
				"status": "success",
				"data": map[string]interface{}{
					"result": []interface{}{
						map[string]interface{}{
							"value": []interface{}{"1710000000", "1048576"},
						},
					},
				},
			})
		default:
			http.NotFound(w, r)
		}
	}))
	defer srv.Close()

	origURL := config.Loki.URL
	defer func() {
		config.Loki.URL = origURL
	}()
	config.Loki.URL = srv.URL

	collector := NewCollector()
	collector.client = srv.Client()

	metric, err := collector.fetchMetrics(context.Background())
	if err != nil {
		t.Fatalf("unexpected error: %v", err)
	}

	if metric.RetentionDays != 0 {
		t.Fatalf("expected retention 0, got %d", metric.RetentionDays)
	}

	if metric.DailyDataSizeMB != 0.04 {
		t.Fatalf("expected daily size 0.04MB, got %v", metric.DailyDataSizeMB)
	}

	if metric.TotalDataSizeMB != 0 {
		t.Fatalf("expected total size 0MB, got %v", metric.TotalDataSizeMB)
	}

	if metric.OldestTimestamp.Before(nowBeforeCall.Add(-2 * time.Second)) {
		t.Fatalf("expected oldest timestamp close to now, got %v", metric.OldestTimestamp)
	}
}

func TestEstimateDataSizeFromChunks_NonOKStatus(t *testing.T) {
	srv := httptest.NewServer(http.HandlerFunc(func(w http.ResponseWriter, r *http.Request) {
		http.Error(w, "boom", http.StatusInternalServerError)
	}))
	defer srv.Close()

	origURL := config.Loki.URL
	defer func() {
		config.Loki.URL = origURL
	}()
	config.Loki.URL = srv.URL

	collector := NewCollector()
	collector.client = srv.Client()

	_, err := collector.estimateDataSizeFromChunks(context.Background())
	if err == nil {
		t.Fatal("expected error for non-200 Loki response")
	}
}

func TestAggregateMetrics_SavesExpectedAggregation(t *testing.T) {
	oldestFirst := time.Date(2026, 5, 1, 8, 0, 0, 0, time.UTC)
	oldestLast := time.Date(2026, 5, 3, 11, 0, 0, 0, time.UTC)

	repo := &lokiRepoStub{
		monthlyMetrics: []*models.LokiMetric{
			{RetentionDays: 3, DailyDataSizeMB: 20, OldestTimestamp: oldestFirst},
			{RetentionDays: 5, DailyDataSizeMB: 30, OldestTimestamp: oldestLast},
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

	if repo.savedAgg.MaxRetentionDays != 5 {
		t.Fatalf("expected MaxRetentionDays=5, got %d", repo.savedAgg.MaxRetentionDays)
	}

	if repo.savedAgg.AvgDailyDataMB != 25 {
		t.Fatalf("expected AvgDailyDataMB=25, got %v", repo.savedAgg.AvgDailyDataMB)
	}

	if repo.savedAgg.EstimatedTotalMB != 125 {
		t.Fatalf("expected EstimatedTotalMB=125, got %v", repo.savedAgg.EstimatedTotalMB)
	}

	if !repo.savedAgg.OldestTimestamp.Equal(oldestLast) {
		t.Fatalf("expected oldest timestamp from last metric, got %v", repo.savedAgg.OldestTimestamp)
	}
}

func TestAggregateMetrics_NoMetrics(t *testing.T) {
	repo := &lokiRepoStub{monthlyMetrics: []*models.LokiMetric{}}
	collector := &Collector{}

	err := collector.aggregateMetrics(context.Background(), repo)
	if err != nil {
		t.Fatalf("unexpected error: %v", err)
	}

	if repo.saveCalls != 0 {
		t.Fatalf("expected zero save calls, got %d", repo.saveCalls)
	}
}
