package scheduler

import (
	"context"
	"testing"
	"time"

	"metrics-collector/pkg/config"
	"metrics-collector/pkg/metrics"
	lokicollector "metrics-collector/pkg/metrics/loki"
	mongodbcollector "metrics-collector/pkg/metrics/mongodb"
	"metrics-collector/pkg/models"
)

type fakeCollector struct {
	name        string
	calls       int
	hasDeadline bool
	deadline    time.Time
	returnError error
}

func (f *fakeCollector) Name() string {
	return f.name
}

func (f *fakeCollector) Collect(ctx context.Context, _ metrics.Repository) error {
	f.calls++
	deadline, ok := ctx.Deadline()
	f.hasDeadline = ok
	f.deadline = deadline
	return f.returnError
}

type fakeRepo struct{}

func (f *fakeRepo) GetMongoDBMonthlyAggregation(context.Context) (*models.MongoAggregation, error) {
	return nil, nil
}

func (f *fakeRepo) GetRabbitMQMonthlyAggregation(context.Context) (*models.RabbitAggregation, error) {
	return nil, nil
}

func (f *fakeRepo) GetK8sMonthlyAggregation(context.Context) (*models.K8sAggregation, error) {
	return nil, nil
}

func (f *fakeRepo) SaveRabbitMQMetric(context.Context, *models.RabbitMQMetric) error { return nil }
func (f *fakeRepo) SaveMongoDBMetric(context.Context, *models.MongoDBMetric) error   { return nil }
func (f *fakeRepo) SaveK8sMetric(context.Context, *models.K8sMetric) error           { return nil }
func (f *fakeRepo) SaveLokiMetric(context.Context, *models.LokiMetric) error         { return nil }

func (f *fakeRepo) SaveRabbitAggregation(context.Context, *models.RabbitAggregation) error {
	return nil
}
func (f *fakeRepo) SaveMongoAggregation(context.Context, *models.MongoAggregation) error { return nil }
func (f *fakeRepo) SaveK8sAggregation(context.Context, *models.K8sAggregation) error     { return nil }
func (f *fakeRepo) SaveLokiAggregation(context.Context, *models.LokiAggregation) error   { return nil }

func (f *fakeRepo) GetLokiMonthlyAggregation(context.Context) (*models.LokiAggregation, error) {
	return nil, nil
}

func (f *fakeRepo) GetRabbitMQMetricsForMonth(context.Context) ([]*models.RabbitMQMetric, error) {
	return nil, nil
}
func (f *fakeRepo) GetMongoDBMetricsForMonth(context.Context) ([]*models.MongoDBMetric, error) {
	return nil, nil
}
func (f *fakeRepo) GetK8sMetricsForMonth(context.Context) ([]*models.K8sMetric, error) {
	return nil, nil
}
func (f *fakeRepo) GetLokiMetricsForMonth(context.Context) ([]*models.LokiMetric, error) {
	return nil, nil
}

func TestNewScheduler_CollectorIntervals(t *testing.T) {
	origTick := config.App.Tick
	origTickMongo := config.App.TickMongoDB
	origTickLoki := config.App.TickLoki
	defer func() {
		config.App.Tick = origTick
		config.App.TickMongoDB = origTickMongo
		config.App.TickLoki = origTickLoki
	}()

	config.App.Tick = 7
	config.App.TickMongoDB = 11
	config.App.TickLoki = 3

	collectors := []metrics.Collector{
		&fakeCollector{name: lokicollector.CollectorName},
		&fakeCollector{name: mongodbcollector.CollectorName},
		&fakeCollector{name: "Other"},
	}

	sch := NewScheduler(collectors, &fakeRepo{})

	if len(sch.collectorsWithIntervals) != 3 {
		t.Fatalf("expected 3 collectors, got %d", len(sch.collectorsWithIntervals))
	}

	intervalByName := map[string]time.Duration{}
	for _, cw := range sch.collectorsWithIntervals {
		intervalByName[cw.Collector.Name()] = cw.Interval
	}

	if intervalByName[lokicollector.CollectorName] != 3*time.Hour {
		t.Fatalf("unexpected Loki interval: %s", intervalByName[lokicollector.CollectorName])
	}

	if intervalByName[mongodbcollector.CollectorName] != 11*time.Minute {
		t.Fatalf("unexpected MongoDB interval: %s", intervalByName[mongodbcollector.CollectorName])
	}

	if intervalByName["Other"] != 7*time.Second {
		t.Fatalf("unexpected default interval: %s", intervalByName["Other"])
	}
}

func TestStartStop_ImmediateCollectAndTimeout(t *testing.T) {
	origTick := config.App.Tick
	defer func() {
		config.App.Tick = origTick
	}()
	config.App.Tick = 3600

	collector := &fakeCollector{name: "Other"}
	sch := NewScheduler([]metrics.Collector{collector}, &fakeRepo{})

	if err := sch.Start(context.Background()); err != nil {
		t.Fatalf("unexpected start error: %v", err)
	}

	if collector.calls < 1 {
		t.Fatalf("expected at least one immediate collect call, got %d", collector.calls)
	}

	if !collector.hasDeadline {
		t.Fatal("expected collector context to have deadline")
	}

	remaining := time.Until(collector.deadline)
	if remaining < 120*time.Second || remaining > 131*time.Second {
		t.Fatalf("expected timeout around 130s, remaining: %s", remaining)
	}

	if err := sch.Stop(); err != nil {
		t.Fatalf("unexpected stop error: %v", err)
	}
}
