package storage

import "go.mongodb.org/mongo-driver/bson/primitive"

// Topology represents topology
type Topology struct {
	ID           primitive.ObjectID `bson:"_id" json:"id"`
	Name         string             `bson:"name" json:"name"`
	Version      int32              `bson:"version" json:"version"`
	Node         *Node              `json:"node"`
	Applications []Application      `bson:"applications"`
}

type Application struct {
	Key  string `bson:"key"`
	Host string `bson:"host"`
}
