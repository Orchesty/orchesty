package storage

import "github.com/mongodb/mongo-go-driver/bson/primitive"

// Topology represents topology
type Topology struct {
	ID   primitive.ObjectID `bson:"_id"json:"id"`
	Name string             `bson:"name"json:"name"`
	Node *Node              `json:"node"`
}
