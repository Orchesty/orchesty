package mongo

import (
	"go.mongodb.org/mongo-driver/v2/bson"
	"go.mongodb.org/mongo-driver/v2/mongo"
	"go.mongodb.org/mongo-driver/v2/mongo/options"
)

func indices() []mongo.IndexModel {
	return []mongo.IndexModel{
		{
			Keys:    bson.D{{Key: "limitKey", Value: 1}},
			Options: options.Index().SetName("IK_limiter_limitKey"),
		},
		{
			Keys: bson.D{
				{Key: "limitKey", Value: 1},
				{Key: "allowedAt", Value: 1},
				{Key: "inProcess", Value: 1},
				{Key: "prioritize", Value: -1},
			},
			Options: options.Index().SetName("IK_limiter_limitKey_allowedAt_inProcess_prioritize"),
		},
		{
			Keys: bson.D{
				{Key: "allowedAt", Value: 1},
				{Key: "created", Value: 1},
			},
			Options: options.Index().SetName("IK_limiter_allowedAt_created"),
		},
		{
			Keys: bson.D{
				{Key: "prioritize", Value: 1},
				{Key: "created", Value: 1},
				{Key: "message.headers.node-id", Value: 1},
				{Key: "message.headers.node-name", Value: 1},
				{Key: "message.headers.user", Value: 1},
				{Key: "message.headers.topology-id", Value: 1},
				{Key: "message.headers.application", Value: 1},
			},
			Options: options.Index().SetName("IK_limiter_prioritize_created_messageHeadersNodeId_messageHeadersNodeName_messageHeadersUser_messageHeadersTopologyId_messageHeadersApplication"),
		},
		{
			Keys: bson.D{
				{Key: "message.headers.correlation-id", Value: 1},
				{Key: "prioritize", Value: 1},
			},
			Options: options.Index().SetName("IK_limiter_messageHeadersCorrelationId_prioritize"),
		},
	}
}
