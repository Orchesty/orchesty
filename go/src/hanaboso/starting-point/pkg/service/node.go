package service

import (
	"github.com/mongodb/mongo-go-driver/bson"
	"github.com/mongodb/mongo-go-driver/bson/objectid"
	"starting-point/pkg/storage"

	log "github.com/sirupsen/logrus"
)

const nodeCollection = "Node"

var nodeDeletedFilter = bson.E{Key: "deleted", Value: false}
var nodeEnabledFilter = bson.E{Key: "enabled", Value: true}

func findMongoNodeByID(nodeID string, topologyID string) *storage.Node {
	var node storage.Node

	innerNodeID, err := objectid.FromHex(nodeID)
	if err != nil {
		log.Error(err)

		return nil
	}

	err = storage.MongoDB.Collection(nodeCollection).FindOne(nil, bson.D{
		{"_id", innerNodeID},
		{"topology", topologyID},
		nodeDeletedFilter,
		nodeEnabledFilter,
	}).Decode(&node)
	if err != nil {
		log.Error(err)

		return nil
	}

	return &node
}

func findMongoNodeByName(nodeName string, topologyID string) []storage.Node {
	var node storage.Node
	var nodes []storage.Node

	cursor, err := storage.MongoDB.Collection(nodeCollection).Find(nil, bson.D{
		{"name", nodeName},
		{"topology", topologyID},
		nodeDeletedFilter,
		nodeEnabledFilter,
	})
	if err != nil {
		log.Error(err)

		return nodes
	}

	defer func() {
		_ = cursor.Close(nil)
	}()

	for cursor.Next(nil) {
		err = cursor.Decode(&node)

		if err != nil {
			log.Error(err)

			return nil
		}

		nodes = append(nodes, node)
	}

	return nodes
}
