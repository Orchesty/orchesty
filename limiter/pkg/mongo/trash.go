package mongo

import (
	"limiter/pkg/enum"
	"limiter/pkg/model"
	"time"

	"github.com/hanaboso/go-utils/pkg/contextx"
	"github.com/pkg/errors"
	"go.mongodb.org/mongo-driver/v2/bson"
)

func fromDto(message *model.MessageDto) bson.M {
	var user *string
	if tmp := message.GetHeader(enum.Header_User); tmp != "" {
		user = &tmp
	}

	return bson.M{
		"nodeId":           message.GetHeader(enum.Header_NodeId),
		"nodeName":         message.GetHeader(enum.Header_NodeName),
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
	ctx, _ := contextx.WithTimeoutSecondsCtx(30)
	_, err := this.userTaskCollection.InsertOne(ctx, data)

	return errors.WithMessage(err, "sending to trash")
}
