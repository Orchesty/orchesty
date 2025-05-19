package model

import (
	"go.mongodb.org/mongo-driver/v2/bson"
)

const (
	Topology   = "topology"
	Node       = "node"
	Time       = "time"
	Parameters = "parameters"
)

type Cron struct {
	ID         bson.ObjectID `bson:"_id" json:"-"`
	Topology   string        `bson:"topology" json:"topology"`
	Node       string        `bson:"node" json:"node"`
	Time       string        `bson:"time" json:"time"`
	Parameters string        `bson:"parameters" json:"parameters"`
}
