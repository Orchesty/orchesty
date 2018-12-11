package storage

import "github.com/mongodb/mongo-go-driver/bson/primitive"

// Node represents node
type Node struct {
	ID        primitive.ObjectID `bson:"_id"json:"id"`
	Name      string             `bson:"name"json:"name"`
	HumanTask *HumanTask         `json:"human_task"`
}
