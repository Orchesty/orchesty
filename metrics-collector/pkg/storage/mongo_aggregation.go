package storage

import (
	"context"
	"metrics-collector/pkg/models"
	"time"

	"go.mongodb.org/mongo-driver/v2/bson"
)

// GetK8sMonthlyAggregation returns aggregation for current month directly from MongoDB.
func (r *MongoRepository) GetK8sMonthlyAggregation(ctx context.Context) (*models.K8sAggregation, error) {
	coll := r.db.Collection(CollectionNamespaceMetrics)

	startOfMonth := time.Date(time.Now().Year(), time.Now().Month(), 1, 0, 0, 0, 0, time.UTC)
	endOfMonth := startOfMonth.AddDate(0, 1, 0)

	pipeline := []bson.M{
		{"$match": bson.M{"timestamp": bson.M{"$gte": startOfMonth, "$lt": endOfMonth}}},
		{"$group": bson.M{
			"_id":           nil,
			"avg_vcpu":      bson.M{"$avg": "$total_vcpu"},
			"max_vcpu":      bson.M{"$max": "$total_vcpu"},
			"avg_memory_mb": bson.M{"$avg": "$total_memory_mb"},
			"max_memory_mb": bson.M{"$max": "$total_memory_mb"},
		}},
		{"$project": bson.M{
			"_id":           0,
			"avg_vcpu":      bson.M{"$round": []interface{}{"$avg_vcpu", 2}},
			"max_vcpu":      bson.M{"$round": []interface{}{"$max_vcpu", 2}},
			"avg_memory_mb": bson.M{"$round": []interface{}{"$avg_memory_mb", 2}},
			"max_memory_mb": bson.M{"$round": []interface{}{"$max_memory_mb", 2}},
		}},
	}

	cursor, err := coll.Aggregate(ctx, pipeline)
	if err != nil {
		return nil, err
	}
	defer cursor.Close(ctx)

	if cursor.Next(ctx) {
		var result struct {
			AvgVCPU     float64 `bson:"avg_vcpu"`
			MaxVCPU     float64 `bson:"max_vcpu"`
			AvgMemoryMB float64 `bson:"avg_memory_mb"`
			MaxMemoryMB float64 `bson:"max_memory_mb"`
		}
		if err := cursor.Decode(&result); err != nil {
			return nil, err
		}

		return &models.K8sAggregation{
			Month:       startOfMonth.Format("2006-01"),
			AvgVCPU:     result.AvgVCPU,
			MaxVCPU:     result.MaxVCPU,
			AvgMemoryMB: result.AvgMemoryMB,
			MaxMemoryMB: result.MaxMemoryMB,
			LastUpdated: time.Now(),
		}, nil
	}

	if err := cursor.Err(); err != nil {
		return nil, err
	}

	return nil, nil
}

// GetLokiMonthlyAggregation returns aggregation for current month directly from MongoDB.
func (r *MongoRepository) GetLokiMonthlyAggregation(ctx context.Context) (*models.LokiAggregation, error) {
	coll := r.db.Collection(CollectionLokiRetentionMetrics)

	startOfMonth := time.Date(time.Now().Year(), time.Now().Month(), 1, 0, 0, 0, 0, time.UTC)
	endOfMonth := startOfMonth.AddDate(0, 1, 0)

	pipeline := []bson.M{
		{"$match": bson.M{"timestamp": bson.M{"$gte": startOfMonth, "$lt": endOfMonth}}},
		{"$sort": bson.M{"timestamp": 1}},
		{"$group": bson.M{
			"_id":                nil,
			"max_retention_days": bson.M{"$max": "$retention_days"},
			"avg_daily_data_mb":  bson.M{"$avg": "$daily_data_size_mb"},
			"oldest_timestamp":   bson.M{"$last": "$oldest_timestamp"},
		}},
		{"$project": bson.M{
			"_id":                0,
			"max_retention_days": 1,
			"avg_daily_data_mb":  1,
			"oldest_timestamp":   1,
			"estimated_total_mb": bson.M{"$multiply": []interface{}{"$avg_daily_data_mb", "$max_retention_days"}},
		}},
	}

	cursor, err := coll.Aggregate(ctx, pipeline)
	if err != nil {
		return nil, err
	}
	defer cursor.Close(ctx)

	if cursor.Next(ctx) {
		var result struct {
			MaxRetentionDays int       `bson:"max_retention_days"`
			AvgDailyDataMB   float64   `bson:"avg_daily_data_mb"`
			EstimatedTotalMB float64   `bson:"estimated_total_mb"`
			OldestTimestamp  time.Time `bson:"oldest_timestamp"`
		}
		if err := cursor.Decode(&result); err != nil {
			return nil, err
		}

		return &models.LokiAggregation{
			Month:            startOfMonth.Format("2006-01"),
			MaxRetentionDays: result.MaxRetentionDays,
			OldestTimestamp:  result.OldestTimestamp,
			AvgDailyDataMB:   result.AvgDailyDataMB,
			EstimatedTotalMB: result.EstimatedTotalMB,
			LastUpdated:      time.Now(),
		}, nil
	}

	if err := cursor.Err(); err != nil {
		return nil, err
	}

	return nil, nil
}

// GetMongoDBMonthlyAggregation returns aggregation for current month directly from MongoDB
func (r *MongoRepository) GetMongoDBMonthlyAggregation(ctx context.Context) (*models.MongoAggregation, error) {
	coll := r.db.Collection(CollectionDBStorageMetrics)

	startOfMonth := time.Date(time.Now().Year(), time.Now().Month(), 1, 0, 0, 0, 0, time.UTC)
	endOfMonth := startOfMonth.AddDate(0, 1, 0)

	pipeline := []bson.M{
		{"$match": bson.M{"timestamp": bson.M{"$gte": startOfMonth, "$lt": endOfMonth}}},
		{"$group": bson.M{
			"_id":                 nil,
			"avg_data_size_mb":    bson.M{"$avg": "$data_size_mb"},
			"max_data_size_mb":    bson.M{"$max": "$data_size_mb"},
			"avg_storage_size_mb": bson.M{"$avg": "$storage_size_mb"},
			"max_storage_size_mb": bson.M{"$max": "$storage_size_mb"},
			"avg_documents":       bson.M{"$avg": "$total_documents"},
		}},
	}

	cursor, err := coll.Aggregate(ctx, pipeline)
	if err != nil {
		return nil, err
	}
	defer cursor.Close(ctx)

	if cursor.Next(ctx) {
		var result struct {
			AvgDataSizeMB    float64 `bson:"avg_data_size_mb"`
			MaxDataSizeMB    float64 `bson:"max_data_size_mb"`
			AvgStorageSizeMB float64 `bson:"avg_storage_size_mb"`
			MaxStorageSizeMB float64 `bson:"max_storage_size_mb"`
			AvgDocuments     float64 `bson:"avg_documents"`
		}
		if err := cursor.Decode(&result); err != nil {
			return nil, err
		}
		return &models.MongoAggregation{
			Month:            startOfMonth.Format("2006-01"),
			AvgDataSizeMB:    result.AvgDataSizeMB,
			MaxDataSizeMB:    result.MaxDataSizeMB,
			AvgStorageSizeMB: result.AvgStorageSizeMB,
			MaxStorageSizeMB: result.MaxStorageSizeMB,
			AvgDocuments:     result.AvgDocuments,
			LastUpdated:      time.Now(),
		}, nil
	}
	return nil, nil
}

// GetRabbitMQMonthlyAggregation returns aggregation for current month directly from MongoDB
func (r *MongoRepository) GetRabbitMQMonthlyAggregation(ctx context.Context) (*models.RabbitAggregation, error) {
	coll := r.db.Collection(CollectionRabbitMQMetrics)

	startOfMonth := time.Date(time.Now().Year(), time.Now().Month(), 1, 0, 0, 0, 0, time.UTC)
	endOfMonth := startOfMonth.AddDate(0, 1, 0)

	pipeline := []bson.M{
		{"$match": bson.M{"timestamp": bson.M{"$gte": startOfMonth, "$lt": endOfMonth}}},
		{"$group": bson.M{
			"_id":          nil,
			"avg_messages": bson.M{"$avg": "$total_messages"},
			"max_messages": bson.M{"$max": "$total_messages"},
			"avg_disk_mb":  bson.M{"$avg": "$total_disk_mb"},
			"max_disk_mb":  bson.M{"$max": "$total_disk_mb"},
			"avg_ram_mb":   bson.M{"$avg": "$total_ram_mb"},
			"max_ram_mb":   bson.M{"$max": "$total_ram_mb"},
		}},
	}

	cursor, err := coll.Aggregate(ctx, pipeline)
	if err != nil {
		return nil, err
	}
	defer cursor.Close(ctx)

	if cursor.Next(ctx) {
		var result struct {
			AvgMessages float64 `bson:"avg_messages"`
			MaxMessages int64   `bson:"max_messages"`
			AvgDiskMB   float64 `bson:"avg_disk_mb"`
			MaxDiskMB   float64 `bson:"max_disk_mb"`
			AvgRamMB    float64 `bson:"avg_ram_mb"`
			MaxRamMB    float64 `bson:"max_ram_mb"`
		}
		if err := cursor.Decode(&result); err != nil {
			return nil, err
		}
		return &models.RabbitAggregation{
			Month:       startOfMonth.Format("2006-01"),
			AvgMessages: result.AvgMessages,
			MaxMessages: result.MaxMessages,
			AvgDiskMB:   result.AvgDiskMB,
			MaxDiskMB:   result.MaxDiskMB,
			AvgRamMB:    result.AvgRamMB,
			MaxRamMB:    result.MaxRamMB,
			LastUpdated: time.Now(),
		}, nil
	}
	return nil, nil
}
