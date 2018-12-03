package storage

import "github.com/mongodb/mongo-go-driver/bson/objectid"

// Node represents node
type Node struct {
	ID   objectid.ObjectID `bson:"_id"json:"id"`
	Name string            `bson:"name"json:"name"`
}
