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
	}
}
