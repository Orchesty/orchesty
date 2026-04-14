package mongo

import (
	"time"

	"github.com/hanaboso/go-mongodb"
	"github.com/hanaboso/pipes/bridge/pkg/config"
	"go.mongodb.org/mongo-driver/v2/bson"
	"go.mongodb.org/mongo-driver/v2/mongo"
	"go.mongodb.org/mongo-driver/v2/mongo/options"
)

type StorageMetric struct {
	StorageSizeMB float64   `bson:"storage_size_mb"`
	Timestamp     time.Time `bson:"timestamp"`
}

type RabbitMetric struct {
	TotalDiskMB float64   `bson:"total_disk_mb"`
	Timestamp   time.Time `bson:"timestamp"`
}

type LokiMetric struct {
	TotalDataSizeMB float64   `bson:"total_data_size_mb"`
	Timestamp       time.Time `bson:"timestamp"`
}

type MetricsReader struct {
	connection         *mongodb.Connection
	storageCollection  *mongo.Collection
	rabbitmqCollection *mongo.Collection
	lokiCollection     *mongo.Collection
}

func NewMetricsReader() *MetricsReader {
	conn := &mongodb.Connection{}
	conn.Connect(config.Metrics.Dsn)
	return &MetricsReader{
		connection:         conn,
		storageCollection:  conn.Database.Collection(config.MetricsCollections.StorageCollection),
		rabbitmqCollection: conn.Database.Collection(config.MetricsCollections.RabbitmqCollection),
		lokiCollection:     conn.Database.Collection(config.MetricsCollections.LokiCollection),
	}
}

func (r *MetricsReader) Close() {
	r.connection.Disconnect()
}

func (r *MetricsReader) GetLatestStorageMetric() (float64, error) {
	var metric StorageMetric
	ctx, cancel := r.connection.Context()
	defer cancel()

	err := r.storageCollection.FindOne(ctx, bson.M{}, options.FindOne().SetSort(bson.D{{Key: "timestamp", Value: -1}})).Decode(&metric)
	if err != nil {
		if err == mongo.ErrNoDocuments {
			return 0, nil
		}
		return 0, err
	}
	return metric.StorageSizeMB, nil
}

func (r *MetricsReader) GetLatestRabbitMetric() (float64, error) {
	var metric RabbitMetric
	ctx, cancel := r.connection.Context()
	defer cancel()

	err := r.rabbitmqCollection.FindOne(ctx, bson.M{}, options.FindOne().SetSort(bson.D{{Key: "timestamp", Value: -1}})).Decode(&metric)
	if err != nil {
		if err == mongo.ErrNoDocuments {
			return 0, nil
		}
		return 0, err
	}
	return metric.TotalDiskMB, nil
}

func (r *MetricsReader) GetLatestLokiMetric() (float64, error) {
	var metric LokiMetric
	ctx, cancel := r.connection.Context()
	defer cancel()

	err := r.lokiCollection.FindOne(ctx, bson.M{}, options.FindOne().SetSort(bson.D{{Key: "timestamp", Value: -1}})).Decode(&metric)
	if err != nil {
		if err == mongo.ErrNoDocuments {
			return 0, nil
		}
		return 0, err
	}
	return metric.TotalDataSizeMB, nil
}
