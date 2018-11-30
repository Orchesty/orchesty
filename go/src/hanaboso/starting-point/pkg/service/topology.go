package service

import (
	"github.com/mongodb/mongo-go-driver/bson"
	"github.com/mongodb/mongo-go-driver/bson/objectid"
	"starting-point/pkg/storage"

	log "github.com/sirupsen/logrus"
)

// Topology represents topology
type Topology struct {
	ID   objectid.ObjectID `bson:"_id"`
	Name string            `bson:"name"`
}

const topologyCollection = "Topology"

var topologyVisibilityFilter = bson.E{Key: "visibility", Value: "public"}
var topologyDeletedFilter = bson.E{Key: "deleted", Value: false}
var topologyEnabledFilter = bson.E{Key: "enabled", Value: true}

// FindTopologyByID finds topology by ID
func FindTopologyByID(ID string) *Topology {
	var topology Topology

	topologyID, err := objectid.FromHex(ID)
	if err != nil {
		log.Error(err)
	}

	err = storage.MongoDB.Collection(topologyCollection).FindOne(nil, bson.D{
		{"_id", topologyID},
		topologyVisibilityFilter,
		topologyDeletedFilter,
		topologyEnabledFilter,
	}).Decode(&topology)
	if err != nil {
		log.Error(err)

		return nil
	}

	return &topology
}

// FindTopologyByName finds topology by name
func FindTopologyByName(name string) *[]Topology {
	var topology Topology
	var topologies []Topology

	cursor, err := storage.MongoDB.Collection(topologyCollection).Find(nil, bson.D{{"name", name}})
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
		topologies = append(topologies, topology)
	}

	return &topologies
}
