package metrics

import (
	"context"

	"metrics-collector/pkg/models"
)

type Repository interface {
	SaveRabbitMQMetric(ctx context.Context, metric *models.RabbitMQMetric) error
	SaveMongoDBMetric(ctx context.Context, metric *models.MongoDBMetric) error
	SaveK8sMetric(ctx context.Context, metric *models.K8sMetric) error
	SaveLokiMetric(ctx context.Context, metric *models.LokiMetric) error

	SaveRabbitAggregation(ctx context.Context, agg *models.RabbitAggregation) error
	SaveMongoAggregation(ctx context.Context, agg *models.MongoAggregation) error
	SaveK8sAggregation(ctx context.Context, agg *models.K8sAggregation) error
	SaveLokiAggregation(ctx context.Context, agg *models.LokiAggregation) error

	GetRabbitMQMetricsForMonth(ctx context.Context) ([]*models.RabbitMQMetric, error)
	GetMongoDBMetricsForMonth(ctx context.Context) ([]*models.MongoDBMetric, error)
	GetK8sMetricsForMonth(ctx context.Context) ([]*models.K8sMetric, error)
	GetLokiMetricsForMonth(ctx context.Context) ([]*models.LokiMetric, error)
}

type Collector interface {
	Collect(ctx context.Context, repo Repository) error

	Name() string
}
