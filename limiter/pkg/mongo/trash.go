package mongo

import (
	"github.com/hanaboso/go-utils/pkg/contextx"
	"github.com/pkg/errors"
	"go.mongodb.org/mongo-driver/bson"
	"limiter/pkg/enum"
	"limiter/pkg/model"
	"time"
)

func fromDto(message *model.MessageDto) bson.M {
	var user *string
	if tmp := message.GetHeader(enum.Header_User); tmp != "" {
		user = &tmp
	}

	return bson.M{
		"nodeId":           message.GetHeader(enum.Header_NodeId),
		"nodeName":         "",
		"topologyId":       message.GetHeader(enum.Header_TopologyId),
		"topologyName":     "",
		"correlationId":    message.GetHeader(enum.Header_CorrelationId),
		"user":             user,
		"created":          time.Now(),
		"updated":          time.Now(),
		"type":             "trash",
		"returnExchange":   "",
		"returnRoutingKey": "",
		"message": bson.M{
			"body":    message.Body,
			"headers": message.Headers,
		},
	}
}

func (this MongoSvc) SendToTrash(message *model.MessageDto) error {
	data := fromDto(message)
	_, err := this.userTaskCollection.InsertOne(contextx.WithTimeoutSecondsCtx(30), data)

	return errors.WithMessage(err, "sending to trash")
}
