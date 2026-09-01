package mongo

import (
	"github.com/hanaboso/go-utils/pkg/contextx"
	"github.com/pkg/errors"
	"go.mongodb.org/mongo-driver/v2/bson"
)

type SnapshotNode struct {
	Id            SnapshotNodeId `bson:"_id"`
	Messages      int            `bson:"messages"`
	TopologyId    string         `bson:"topologyId"`
	ApplicationId string         `bson:"applicationId"`
}

type SnapshotNodeId struct {
	NodeId   string `bson:"nodeId"`
	NodeName string `bson:"nodeName"`
	UserId   string `bson:"userId"`
}

func (this MongoSvc) Snapshot() ([]SnapshotNode, error) {
	ctx, _ := contextx.WithTimeoutSecondsCtx(30)

	pipeline := bson.A{
		bson.D{{
			"$group", bson.D{
				{"_id", bson.D{
					{"nodeId", "$message.headers.node-id"},
					{"nodeName", "$message.headers.node-name"},
					{"userId", "$message.headers.user"},
				}},
				{"messages", bson.D{{"$sum", 1}}},
				{"topologyId", bson.D{{"$first", "$message.headers.topology-id"}}},
				{"applicationId", bson.D{{"$first", "$message.headers.application"}}},
			},
		}},
	}

	cursor, err := this.collection.Aggregate(ctx, pipeline)
	if err != nil {
		return nil, errors.Wrap(err, "snapshot aggregation")
	}
	defer cursor.Close(ctx)

	var nodes []SnapshotNode
	if err = cursor.All(ctx, &nodes); err != nil {
		return nil, errors.Wrap(err, "snapshot decode")
	}

	return nodes, nil
}
