package rabbitmq

import (
	"context"
	"io"
	"net/http"
	"strings"
	"testing"
	"time"

	"metrics-collector/pkg/config"
	"metrics-collector/pkg/models"
)

type rabbitRepoStub struct {
	monthlyMetrics []*models.RabbitMQMetric
	savedAgg       *models.RabbitAggregation
	saveCalls      int
}

func (r *rabbitRepoStub) SaveRabbitMQMetric(context.Context, *models.RabbitMQMetric) error {
	return nil
}
func (r *rabbitRepoStub) SaveMongoDBMetric(context.Context, *models.MongoDBMetric) error { return nil }
func (r *rabbitRepoStub) SaveK8sMetric(context.Context, *models.K8sMetric) error         { return nil }
func (r *rabbitRepoStub) SaveLokiMetric(context.Context, *models.LokiMetric) error       { return nil }

func (r *rabbitRepoStub) SaveRabbitAggregation(_ context.Context, agg *models.RabbitAggregation) error {
	r.saveCalls++
	r.savedAgg = agg
	return nil
}

func (r *rabbitRepoStub) SaveMongoAggregation(context.Context, *models.MongoAggregation) error {
	return nil
}
func (r *rabbitRepoStub) SaveK8sAggregation(context.Context, *models.K8sAggregation) error {
	return nil
}
func (r *rabbitRepoStub) SaveLokiAggregation(context.Context, *models.LokiAggregation) error {
	return nil
}

func (r *rabbitRepoStub) GetRabbitMQMetricsForMonth(context.Context) ([]*models.RabbitMQMetric, error) {
	return r.monthlyMetrics, nil
}
func (r *rabbitRepoStub) GetMongoDBMetricsForMonth(context.Context) ([]*models.MongoDBMetric, error) {
	return nil, nil
}
func (r *rabbitRepoStub) GetK8sMetricsForMonth(context.Context) ([]*models.K8sMetric, error) {
	return nil, nil
}
func (r *rabbitRepoStub) GetLokiMetricsForMonth(context.Context) ([]*models.LokiMetric, error) {
	return nil, nil
}

func TestIsQueueExcluded(t *testing.T) {
	original := config.RabbitMQ.ExcludedQueues
	defer func() {
		config.RabbitMQ.ExcludedQueues = original
	}()

	config.RabbitMQ.ExcludedQueues = []string{"skip.me", "dead.letter"}

	collector := NewCollector()

	if !collector.isQueueExcluded("skip.me") {
		t.Fatal("expected queue to be excluded")
	}

	if collector.isQueueExcluded("process.me") {
		t.Fatal("expected queue not to be excluded")
	}
}

func TestParseResponse(t *testing.T) {
	collector := NewCollector()

	t.Run("success", func(t *testing.T) {
		resp := &http.Response{
			StatusCode: http.StatusOK,
			Body: io.NopCloser(strings.NewReader(`{
				"items":[{"name":"q1","messages":2,"message_bytes_persistent":1024,"message_bytes_ram":2048,"members":["a"]}],
				"page_count":3,
				"total_count":10
			}`)),
		}

		items, pageCount, err := collector.parseResponse(resp)
		if err != nil {
			t.Fatalf("unexpected error: %v", err)
		}

		if pageCount != 3 {
			t.Fatalf("expected pageCount 3, got %d", pageCount)
		}

		if len(items) != 1 || items[0].Name != "q1" {
			t.Fatalf("unexpected parsed items: %#v", items)
		}
	})

	t.Run("http error", func(t *testing.T) {
		resp := &http.Response{
			StatusCode: http.StatusBadGateway,
			Body:       io.NopCloser(strings.NewReader("backend down")),
		}

		_, _, err := collector.parseResponse(resp)
		if err == nil {
			t.Fatal("expected error for non-200 response")
		}
	})

	t.Run("invalid json", func(t *testing.T) {
		resp := &http.Response{
			StatusCode: http.StatusOK,
			Body:       io.NopCloser(strings.NewReader("not-json")),
		}

		_, _, err := collector.parseResponse(resp)
		if err == nil {
			t.Fatal("expected error for invalid json")
		}
	})
}

func TestAggregateMetrics_SavesExpectedAggregation(t *testing.T) {
	repo := &rabbitRepoStub{
		monthlyMetrics: []*models.RabbitMQMetric{
			{TotalMessages: 10, TotalDiskMB: 20.5, TotalRamMB: 5.5},
			{TotalMessages: 20, TotalDiskMB: 30.5, TotalRamMB: 7.5},
		},
	}

	collector := NewCollector()
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

	if repo.savedAgg.AvgMessages != 15 {
		t.Fatalf("expected AvgMessages=15, got %v", repo.savedAgg.AvgMessages)
	}

	if repo.savedAgg.MaxMessages != 20 {
		t.Fatalf("expected MaxMessages=20, got %d", repo.savedAgg.MaxMessages)
	}

	if repo.savedAgg.AvgDiskMB != 25.5 {
		t.Fatalf("expected AvgDiskMB=25.5, got %v", repo.savedAgg.AvgDiskMB)
	}

	if repo.savedAgg.MaxDiskMB != 30.5 {
		t.Fatalf("expected MaxDiskMB=30.5, got %v", repo.savedAgg.MaxDiskMB)
	}

	if repo.savedAgg.AvgRamMB != 6.5 {
		t.Fatalf("expected AvgRamMB=6.5, got %v", repo.savedAgg.AvgRamMB)
	}

	if repo.savedAgg.MaxRamMB != 7.5 {
		t.Fatalf("expected MaxRamMB=7.5, got %v", repo.savedAgg.MaxRamMB)
	}

	if repo.savedAgg.Month != time.Now().Format("2006-01") {
		t.Fatalf("unexpected month %q", repo.savedAgg.Month)
	}
}

func TestAggregateMetrics_NoMetrics(t *testing.T) {
	repo := &rabbitRepoStub{monthlyMetrics: []*models.RabbitMQMetric{}}
	collector := NewCollector()

	err := collector.aggregateMetrics(context.Background(), repo)
	if err != nil {
		t.Fatalf("unexpected error: %v", err)
	}

	if repo.saveCalls != 0 {
		t.Fatalf("expected zero save calls, got %d", repo.saveCalls)
	}
}
