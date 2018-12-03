package service

import (
	"github.com/mongodb/mongo-go-driver/bson"
	"github.com/mongodb/mongo-go-driver/bson/objectid"
	"starting-point/pkg/storage"

	log "github.com/sirupsen/logrus"
)

const topologyCollection = "Topology"

var topologyVisibilityFilter = bson.E{Key: "visibility", Value: "public"}
var topologyDeletedFilter = bson.E{Key: "deleted", Value: false}
var topologyEnabledFilter = bson.E{Key: "enabled", Value: true}

func findMongoTopologyByID(topologyID string, nodeID string) *storage.Topology {
	var topology storage.Topology

	innerTopologyID, err := objectid.FromHex(topologyID)
	if err != nil {
		log.Error(err)

		return nil
	}

	err = storage.MongoDB.Collection(topologyCollection).FindOne(nil, bson.D{
		{"_id", innerTopologyID},
		topologyVisibilityFilter,
		topologyDeletedFilter,
		topologyEnabledFilter,
	}).Decode(&topology)
	if err != nil {
		log.Error(err)

		return nil
	}

	topology.Node = findMongoNodeByID(nodeID, topologyID)

	return &topology
}

func findMongoTopologyByName(topologyName string, nodeName string) []storage.Topology {
	var topology storage.Topology
	var topologies []storage.Topology

	cursor, err := storage.MongoDB.Collection(topologyCollection).Find(nil, bson.D{
		{"name", topologyName},
		topologyVisibilityFilter,
		topologyDeletedFilter,
		topologyEnabledFilter,
	})
	if err != nil {
		log.Error(err)
	}

	defer func() {
		_ = cursor.Close(nil)
	}()

	for cursor.Next(nil) {
		err = cursor.Decode(&topology)
		if err != nil {
			log.Error(err)

			return nil
		}

		nodes := findMongoNodeByName(nodeName, topology.ID.Hex())
		if len(nodes) > 0 {
			topology.Node = &nodes[0]
			topologies = append(topologies, topology)
		}
	}

	return topologies
}
