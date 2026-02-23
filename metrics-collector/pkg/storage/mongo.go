package storage

import (
	"context"
	"fmt"
	"time"

	"metrics-collector/pkg/config"
	"metrics-collector/pkg/models"

	"github.com/hanaboso/go-mongodb"
	"go.mongodb.org/mongo-driver/v2/bson"
	"go.mongodb.org/mongo-driver/v2/mongo"
	"go.mongodb.org/mongo-driver/v2/mongo/options"
)

const (
	CollectionRabbitMQMetrics         = "rabbitmq_metrics"
	CollectionMonthlyRabbitAggregates = "monthly_rabbit_aggregates"

	CollectionDBStorageMetrics         = "db_storage_metrics"
	CollectionMonthlyStorageAggregates = "monthly_storage_aggregates"

	CollectionNamespaceMetrics  = "resource_metrics"
	CollectionMonthlyAggregates = "monthly_resource_aggregates"

	CollectionLokiRetentionMetrics  = "loki_retention_metrics"
	CollectionMonthlyLokiAggregates = "monthly_loki_aggregates"

	FieldTimestamp = "timestamp"
	FieldMonth     = "month"
)

type MongoRepository struct {
	connection *mongodb.Connection
	db         *mongo.Database
}

func NewMongoRepository(ctx context.Context) (*MongoRepository, error) {
	mongoDbCon := &mongodb.Connection{}
	mongoDbCon.Connect(config.Mongo.Dsn)

	repo := &MongoRepository{
		connection: mongoDbCon,
		db:         mongoDbCon.Database,
	}

	if err := repo.createIndexes(ctx); err != nil {
		return nil, fmt.Errorf("failed to create indexes: %w", err)
	}

	return repo, nil
}

func (r *MongoRepository) createIndexes(ctx context.Context) error {
	month := int32(30 * 24 * 60 * 60)

	collections := map[string][]mongo.IndexModel{
		CollectionRabbitMQMetrics: {
			{
				Keys:    bson.M{FieldTimestamp: 1},
				Options: options.Index().SetExpireAfterSeconds(month),
			},
		},
		CollectionDBStorageMetrics: {
			{
				Keys:    bson.M{FieldTimestamp: 1},
				Options: options.Index().SetExpireAfterSeconds(month),
			},
		},
		CollectionMonthlyStorageAggregates: {
			{
				Keys: bson.M{FieldMonth: 1},
			},
		},
		CollectionNamespaceMetrics: {
			{
				Keys:    bson.M{FieldTimestamp: 1},
				Options: options.Index().SetExpireAfterSeconds(month),
			},
		},
		CollectionMonthlyAggregates: {
			{
				Keys: bson.M{FieldMonth: 1},
			},
		},
		CollectionLokiRetentionMetrics: {
			{
				Keys:    bson.M{FieldTimestamp: 1},
				Options: options.Index().SetExpireAfterSeconds(month),
			},
		},
		CollectionMonthlyRabbitAggregates: {
			{
				Keys: bson.M{FieldMonth: 1},
			},
		},
		CollectionMonthlyLokiAggregates: {
			{
				Keys: bson.M{FieldMonth: 1},
			},
		},
	}

	for collName, indexes := range collections {
		coll := r.db.Collection(collName)
		indexView := coll.Indexes()
		_, err := indexView.CreateMany(ctx, indexes)
		if err != nil && !mongo.IsDuplicateKeyError(err) {
			return fmt.Errorf("failed to create indexes for %s: %w", collName, err)
		}
	}

	return nil
}

func (r *MongoRepository) SaveRabbitMQMetric(ctx context.Context, metric *models.RabbitMQMetric) error {
	coll := r.db.Collection(CollectionRabbitMQMetrics)
	_, err := coll.InsertOne(ctx, metric)
	if err != nil {
		return fmt.Errorf("failed to save RabbitMQ metric: %w", err)
	}
	return nil
}

func (r *MongoRepository) SaveMongoDBMetric(ctx context.Context, metric *models.MongoDBMetric) error {
	coll := r.db.Collection(CollectionDBStorageMetrics)
	_, err := coll.InsertOne(ctx, metric)
	if err != nil {
		return fmt.Errorf("failed to save MongoDB metric: %w", err)
	}
	return nil
}

func (r *MongoRepository) SaveMongoAggregation(ctx context.Context, agg *models.MongoAggregation) error {
	coll := r.db.Collection(CollectionMonthlyStorageAggregates)
	_, err := coll.UpdateOne(
		ctx,
		bson.M{FieldMonth: agg.Month},
		bson.M{"$set": agg},
		options.UpdateOne().SetUpsert(true),
	)
	if err != nil {
		return fmt.Errorf("failed to save MongoDB aggregation: %w", err)
	}
	return nil
}

func (r *MongoRepository) SaveK8sMetric(ctx context.Context, metric *models.K8sMetric) error {
	coll := r.db.Collection(CollectionNamespaceMetrics)
	_, err := coll.InsertOne(ctx, metric)
	if err != nil {
		return fmt.Errorf("failed to save K8s metric: %w", err)
	}
	return nil
}

func (r *MongoRepository) SaveK8sAggregation(ctx context.Context, agg *models.K8sAggregation) error {
	coll := r.db.Collection(CollectionMonthlyAggregates)
	_, err := coll.UpdateOne(
		ctx,
		bson.M{FieldMonth: agg.Month},
		bson.M{"$set": agg},
		options.UpdateOne().SetUpsert(true),
	)
	if err != nil {
		return fmt.Errorf("failed to save K8s aggregation: %w", err)
	}
	return nil
}

func (r *MongoRepository) SaveLokiMetric(ctx context.Context, metric *models.LokiMetric) error {
	coll := r.db.Collection(CollectionLokiRetentionMetrics)
	_, err := coll.InsertOne(ctx, metric)
	if err != nil {
		return fmt.Errorf("failed to save Loki metric: %w", err)
	}
	return nil
}

func (r *MongoRepository) SaveLokiAggregation(ctx context.Context, agg *models.LokiAggregation) error {
	coll := r.db.Collection(CollectionMonthlyLokiAggregates)
	_, err := coll.UpdateOne(
		ctx,
		bson.M{FieldMonth: agg.Month},
		bson.M{"$set": agg},
		options.UpdateOne().SetUpsert(true),
	)
	if err != nil {
		return fmt.Errorf("failed to save Loki aggregation: %w", err)
	}
	return nil
}

func (r *MongoRepository) SaveRabbitAggregation(ctx context.Context, agg *models.RabbitAggregation) error {
	coll := r.db.Collection(CollectionMonthlyRabbitAggregates)
	_, err := coll.UpdateOne(
		ctx,
		bson.M{FieldMonth: agg.Month},
		bson.M{"$set": agg},
		options.UpdateOne().SetUpsert(true),
	)
	if err != nil {
		return fmt.Errorf("failed to save RabbitMQ aggregation: %w", err)
	}
	return nil
}

func (r *MongoRepository) GetMongoDBMetricsForMonth(ctx context.Context) ([]*models.MongoDBMetric, error) {
	coll := r.db.Collection(CollectionDBStorageMetrics)

	startOfMonth := time.Date(time.Now().Year(), time.Now().Month(), 1, 0, 0, 0, 0, time.UTC)
	endOfMonth := startOfMonth.AddDate(0, 1, 0)

	filter := bson.M{
		FieldTimestamp: bson.M{
			"$gte": startOfMonth,
			"$lt":  endOfMonth,
		},
	}

	cursor, err := coll.Find(ctx, filter)
	if err != nil {
		return nil, fmt.Errorf("failed to find MongoDB metrics: %w", err)
	}
	defer cursor.Close(ctx)

	var metrics []*models.MongoDBMetric
	if err = cursor.All(ctx, &metrics); err != nil {
		return nil, fmt.Errorf("failed to decode MongoDB metrics: %w", err)
	}

	return metrics, nil
}

func (r *MongoRepository) GetK8sMetricsForMonth(ctx context.Context) ([]*models.K8sMetric, error) {
	coll := r.db.Collection(CollectionNamespaceMetrics)

	startOfMonth := time.Date(time.Now().Year(), time.Now().Month(), 1, 0, 0, 0, 0, time.UTC)
	endOfMonth := startOfMonth.AddDate(0, 1, 0)

	filter := bson.M{
		FieldTimestamp: bson.M{
			"$gte": startOfMonth,
			"$lt":  endOfMonth,
		},
	}

	cursor, err := coll.Find(ctx, filter)
	if err != nil {
		return nil, fmt.Errorf("failed to find K8s metrics: %w", err)
	}
	defer cursor.Close(ctx)

	var metrics []*models.K8sMetric
	if err = cursor.All(ctx, &metrics); err != nil {
		return nil, fmt.Errorf("failed to decode K8s metrics: %w", err)
	}

	return metrics, nil
}

func (r *MongoRepository) GetRabbitMQMetricsForMonth(ctx context.Context) ([]*models.RabbitMQMetric, error) {
	coll := r.db.Collection(CollectionRabbitMQMetrics)

	startOfMonth := time.Date(time.Now().Year(), time.Now().Month(), 1, 0, 0, 0, 0, time.UTC)
	endOfMonth := startOfMonth.AddDate(0, 1, 0)

	filter := bson.M{
		FieldTimestamp: bson.M{
			"$gte": startOfMonth,
			"$lt":  endOfMonth,
		},
	}

	cursor, err := coll.Find(ctx, filter)
	if err != nil {
		return nil, fmt.Errorf("failed to find RabbitMQ metrics: %w", err)
	}
	defer cursor.Close(ctx)

	var metrics []*models.RabbitMQMetric
	if err = cursor.All(ctx, &metrics); err != nil {
		return nil, fmt.Errorf("failed to decode RabbitMQ metrics: %w", err)
	}

	return metrics, nil
}

func (r *MongoRepository) GetLokiMetricsForMonth(ctx context.Context) ([]*models.LokiMetric, error) {
	coll := r.db.Collection(CollectionLokiRetentionMetrics)

	startOfMonth := time.Date(time.Now().Year(), time.Now().Month(), 1, 0, 0, 0, 0, time.UTC)
	endOfMonth := startOfMonth.AddDate(0, 1, 0)

	filter := bson.M{
		FieldTimestamp: bson.M{
			"$gte": startOfMonth,
			"$lt":  endOfMonth,
		},
	}

	cursor, err := coll.Find(ctx, filter)
	if err != nil {
		return nil, fmt.Errorf("failed to find Loki metrics: %w", err)
	}
	defer cursor.Close(ctx)

	var metrics []*models.LokiMetric
	if err = cursor.All(ctx, &metrics); err != nil {
		return nil, fmt.Errorf("failed to decode Loki metrics: %w", err)
	}

	return metrics, nil
}

func (r *MongoRepository) GetDB() *mongo.Database {
	return r.db
}

func (r *MongoRepository) Close() error {
	if r.connection != nil {
		r.connection.Disconnect()
	}
	return nil
}
