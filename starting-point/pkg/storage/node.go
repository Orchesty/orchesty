package storage

import (
	"fmt"
	"go.mongodb.org/mongo-driver/bson/primitive"
)

// Node represents node
type Node struct {
	ID   primitive.ObjectID `bson:"_id" json:"id"`
	Name string             `bson:"name" json:"name"`
}

func (n Node) Exchange() string {
	return fmt.Sprintf("node.%s.hx", n.ID.Hex())
}

func (n Node) Queue() string {
	return fmt.Sprintf("node.%s.1", n.ID.Hex())
}
