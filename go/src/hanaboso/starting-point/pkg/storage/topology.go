package storage

import "github.com/mongodb/mongo-go-driver/bson/objectid"

// Topology represents topology
type Topology struct {
	ID   objectid.ObjectID `bson:"_id"json:"id"`
	Name string            `bson:"name"json:"name"`
	Node *Node             `json:"node"`
}
