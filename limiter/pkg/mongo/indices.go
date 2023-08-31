package mongo

import (
	"go.mongodb.org/mongo-driver/bson"
	"go.mongodb.org/mongo-driver/mongo"
)

func indices() []mongo.IndexModel {
	return []mongo.IndexModel{
		{
			Keys: bson.D{{"limitKey", 1}},
		},
		{
			Keys: bson.D{{"prioritize", -1}},
		},
		{
			Keys: bson.D{
				{"prioritize", -1},
				{"allowedAt", 1},
			},
		},
		{
			Keys: bson.D{
				{"limitKey", 1},
				{"prioritize", -1},
				{"allowedAt", 1},
			},
		},
	}
}
