package models

import "time"

type RabbitMQMetric struct {
	ID            interface{} `bson:"_id,omitempty"`
	TotalMessages int64       `bson:"total_messages"`
	TotalDiskMB   float64     `bson:"total_disk_mb"`
	TotalRamMB    float64     `bson:"total_ram_mb"`
	Timestamp     time.Time   `bson:"timestamp"`
}

type MongoDBMetric struct {
	ID               interface{} `bson:"_id,omitempty"`
	TotalDocuments   int64       `bson:"total_documents"`
	DataSizeMB       float64     `bson:"data_size_mb"`
	StorageSizeMB    float64     `bson:"storage_size_mb"`
	CollectionsCount int         `bson:"collections_count"`
	Timestamp        time.Time   `bson:"timestamp"`
}

type K8sMetric struct {
	ID            interface{} `bson:"_id,omitempty"`
	TotalVCPU     float64     `bson:"total_vcpu"`
	TotalMemoryMB float64     `bson:"total_memory_mb"`
	Timestamp     time.Time   `bson:"timestamp"`
}

type LokiMetric struct {
	ID              interface{} `bson:"_id,omitempty"`
	OldestTimestamp time.Time   `bson:"oldest_timestamp"`
	RetentionDays   int         `bson:"retention_days"`
	DailyDataSizeMB float64     `bson:"daily_data_size_mb"`
	TotalDataSizeMB float64     `bson:"total_data_size_mb"`
	Timestamp       time.Time   `bson:"timestamp"`
}

type RabbitAggregation struct {
	ID          interface{} `bson:"_id,omitempty"`
	Month       string      `bson:"month"` // YYYY-MM
	AvgMessages float64     `bson:"avg_messages"`
	MaxMessages int64       `bson:"max_messages"`
	AvgDiskMB   float64     `bson:"avg_disk_mb"`
	MaxDiskMB   float64     `bson:"max_disk_mb"`
	AvgRamMB    float64     `bson:"avg_ram_mb"`
	MaxRamMB    float64     `bson:"max_ram_mb"`
	LastUpdated time.Time   `bson:"last_updated"`
}

type MongoAggregation struct {
	ID               interface{} `bson:"_id,omitempty"`
	Month            string      `bson:"month"` // YYYY-MM
	AvgDataSizeMB    float64     `bson:"avg_data_size_mb"`
	MaxDataSizeMB    float64     `bson:"max_data_size_mb"`
	AvgStorageSizeMB float64     `bson:"avg_storage_size_mb"`
	MaxStorageSizeMB float64     `bson:"max_storage_size_mb"`
	AvgDocuments     float64     `bson:"avg_documents"`
	LastUpdated      time.Time   `bson:"last_updated"`
}

type K8sAggregation struct {
	ID          interface{} `bson:"_id,omitempty"`
	Month       string      `bson:"month"` // YYYY-MM
	AvgVCPU     float64     `bson:"avg_vcpu"`
	MaxVCPU     float64     `bson:"max_vcpu"`
	AvgMemoryMB float64     `bson:"avg_memory_mb"`
	MaxMemoryMB float64     `bson:"max_memory_mb"`
	LastUpdated time.Time   `bson:"last_updated"`
}

type LokiAggregation struct {
	ID               interface{} `bson:"_id,omitempty"`
	Month            string      `bson:"month"` // YYYY-MM
	MaxRetentionDays int         `bson:"max_retention_days"`
	AvgDailyDataMB   float64     `bson:"avg_daily_data_mb"`
	EstimatedTotalMB float64     `bson:"estimated_total_mb"`
	OldestTimestamp  time.Time   `bson:"oldest_timestamp"`
	LastUpdated      time.Time   `bson:"last_updated"`
}
