package storage

import "go.mongodb.org/mongo-driver/bson/primitive"

// HumanTask represents HumanTask data
type HumanTask struct {
	ID            primitive.ObjectID `bson:"_id"json:"id"`
	ParentID      string             `bson:"parentId"json:"parent_id"`
	ProcessID     string             `bson:"processId"json:"process_id"`
	SequenceID    string             `bson:"sequenceId"json:"sequence_id"`
	CorrelationID string             `bson:"correlationId"json:"correlation_id"`
	ParentProcess string             `bson:"parentProcess"json:"parent_process"`
	ContentType   string             `bson:"contentType"json:"content_type"`
}
