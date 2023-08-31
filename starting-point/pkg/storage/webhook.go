package storage

import "go.mongodb.org/mongo-driver/bson/primitive"

// Webhook represents webhook
type Webhook struct {
	ID          primitive.ObjectID `bson:"_id"json:"id"`
	User        string             `bson:"user"json:"user"`
	Token       string             `bson:"token"json:"token"`
	Node        string             `bson:"node"json:"node"`
	Topology    string             `bson:"topology"json:"topology"`
	Application string             `bson:"application"json:"application"`
}
