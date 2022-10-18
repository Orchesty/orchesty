package model

import (
	"go.mongodb.org/mongo-driver/bson"
	"time"
)

type UpdateProcess struct {
	Filter bson.M
	Update interface{}
}

type Process struct {
	Id          string     `bson:"_id"`
	Ok          int        `bson:"ok"`
	Nok         int        `bson:"nok"`
	Total       int        `bson:"total"`
	TopologyId  string     `bson:"topologyId"`
	User        string     `bson:"user"`
	Created     time.Time  `bson:"created"`
	Finished    *time.Time `bson:"finished"`
	SystemEvent bool       `bson:"systemEvent"`
}

type ErrorMessage struct {
	CorrelationId string                 `bson:"correlationId"`
	ProcessId     string                 `bson:"processId"`
	Body          string                 `bson:"body"`
	Headers       map[string]interface{} `bson:"headers"`
}

func (p Process) IsOk() bool {
	return p.Nok <= 0
}

func (p Process) IsFinished() bool {
	return p.Total <= p.Ok+p.Nok
}
