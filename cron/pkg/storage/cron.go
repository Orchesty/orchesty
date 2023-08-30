package storage

import "go.mongodb.org/mongo-driver/bson/primitive"

const topology = "topology"
const node = "node"
const time = "time"
const command = "command"

// Cron represents cron
type Cron struct {
	ID       primitive.ObjectID `bson:"_id"json:"id"`
	Topology string             `bson:"topology"json:"topology"`
	Node     string             `bson:"node"json:"node"`
	Time     string             `bson:"time"json:"time"`
	Command  string             `bson:"command"json:"command"`
}
