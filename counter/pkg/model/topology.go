package model

import "go.mongodb.org/mongo-driver/v2/bson"

type Topology struct {
	Id      bson.ObjectID `bson:"_id"`
	Name    string        `bson:"name"`
	Version int           `bson:"version"`
}
