package storage

import "go.mongodb.org/mongo-driver/mongo"

// GetIndexes - get indexes to create
func GetIndexes() []mongo.IndexModel {
	return []mongo.IndexModel{
		{
			Keys: []string{"limitkey"},
		},
		{
			Keys: []string{"limitkey", "created"},
		},
		{
			Keys: []string{"groupkey"},
		},
	}
}
