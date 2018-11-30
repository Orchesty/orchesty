package service

import (
	"github.com/mongodb/mongo-go-driver/bson"
	"github.com/mongodb/mongo-go-driver/bson/objectid"
	"starting-point/pkg/storage"

	log "github.com/sirupsen/logrus"
)

// Node represents node
type Node struct {
	ID   objectid.ObjectID `bson:"_id"`
	Name string            `bson:"name"`
}

const nodeCollection = "Node"

var nodeDeletedFilter = bson.E{Key: "deleted", Value: false}
var nodeEnabledFilter = bson.E{Key: "enabled", Value: true}

// FindNodeByID finds node by ID
func FindNodeByID(ID string) *Node {
	var node Node

	nodeID, err := objectid.FromHex(ID)
	if err != nil {
		log.Error(err)
	}

	err = storage.MongoDB.Collection(nodeCollection).FindOne(nil, bson.D{
		{"_id", nodeID},
		nodeDeletedFilter,
		nodeEnabledFilter,
	}).Decode(&node)
	if err != nil {
		log.Error(err)

		return nil
	}

	return &node
}

// FindNodeByName finds node by name
func FindNodeByName(name string) *[]Node {
	var node Node
	var nodes []Node

	cursor, err := storage.MongoDB.Collection(nodeCollection).Find(nil, bson.D{{"name", name}})
	if err != nil {
		log.Error(err)
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

	return &nodes
}
