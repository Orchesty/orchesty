package model

import "go.mongodb.org/mongo-driver/bson/primitive"

const (
	Topology   = "topology"
	Node       = "node"
	Time       = "time"
	Parameters = "parameters"
)

type Cron struct {
	ID         primitive.ObjectID `bson:"_id" json:"-"`
	Topology   string             `bson:"topology" json:"topology"`
	Node       string             `bson:"node" json:"node"`
	Time       string             `bson:"time" json:"time"`
	Parameters string             `bson:"parameters" json:"parameters"`
}
