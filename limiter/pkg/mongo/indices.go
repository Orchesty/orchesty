package mongo

import (
	"go.mongodb.org/mongo-driver/v2/bson"
	"go.mongodb.org/mongo-driver/v2/mongo"
)

func indices() []mongo.IndexModel {
	return []mongo.IndexModel{
		{
			Keys: bson.D{{"limitKey", 1}},
		},
		{
			Keys: bson.D{
				{"limitKey", 1},
				{"allowedAt", 1},
				{"inProcess", 1},
				{"prioritize", -1},
			},
		},
		{
			Keys: bson.D{
				{"allowedAt", 1},
				{"created", 1},
			},
		},
		{
			Keys: bson.D{
				{"message.headers.node-id", 1},
				{"message.headers.user", 1},
				{"message.headers.topology-id", 1},
				{"message.headers.application", 1},
			},
		},
	}
}
