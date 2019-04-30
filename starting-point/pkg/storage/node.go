package storage

import "go.mongodb.org/mongo-driver/bson/primitive"

// Node represents node
type Node struct {
	ID        primitive.ObjectID `bson:"_id"json:"id"`
	Name      string             `bson:"name"json:"name"`
	HumanTask *HumanTask         `json:"human_task"`
}
