package model

type Topology struct {
	Id      string `bson:"_id"`
	Name    string `bson:"name"`
	Version int    `bson:"version"`
}
