package mongo

import (
	"go.mongodb.org/mongo-driver/v2/bson"
	"go.mongodb.org/mongo-driver/v2/mongo"
	"go.mongodb.org/mongo-driver/v2/mongo/options"
)

func indices() []mongo.IndexModel {
	return []mongo.IndexModel{
		{
			Keys:    bson.D{{"limitKey", 1}},
			Options: options.Index().SetName("IK_limiter_limitKey"),
		},
		{
			Keys: bson.D{
				{"limitKey", 1},
				{"allowedAt", 1},
				{"inProcess", 1},
				{"prioritize", -1},
			},
			Options: options.Index().SetName("IK_limiter_limitKey_allowedAt_inProcess_prioritize"),
		},
		{
			Keys: bson.D{
				{"allowedAt", 1},
				{"created", 1},
			},
			Options: options.Index().SetName("IK_limiter_allowedAt_created"),
		},
		{
			Keys: bson.D{
				{"prioritize", 1},
				{"created", 1},
				{"message.headers.node-id", 1},
				{"message.headers.node-name", 1},
				{"message.headers.user", 1},
				{"message.headers.topology-id", 1},
				{"message.headers.application", 1},
			},
			Options: options.Index().SetName("IK_limiter_prioritize_created_messageHeadersNodeId_messageHeadersNodeName_messageHeadersUser_messageHeadersTopologyId_messageHeadersApplication"),
		},
		{
			Keys: bson.D{
				{"message.headers.correlation-id", 1},
				{"prioritize", 1},
			},
			Options: options.Index().SetName("IK_limiter_messageHeadersCorrelationId_prioritize"),
		},
	}
}
