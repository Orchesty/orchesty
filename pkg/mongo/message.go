package mongo

import (
	"github.com/hanaboso/pipes/bridge/pkg/enum"
	"github.com/hanaboso/pipes/bridge/pkg/model"
	"go.mongodb.org/mongo-driver/bson"
	"time"
)

const (
	Type_Trash    = "trash"
	Type_UserTask = "userTask"
)

func fromDto(dto model.ProcessResult, nodeName, topologyName string) bson.M {
	msg := dto.Message()
	typed := Type_Trash
	if dto.Status() == enum.ProcessStatus_Continue || dto.Status() == enum.ProcessStatus_StopAndOk {
		typed = Type_UserTask
	}

	msg.KeepRepeatHeaders = false
	msg.ClearHeaders()
	err := dto.Error()
	if err != nil {
		msg.SetHeader(enum.Header_ResultMessage, err.Error())
	}

	return bson.M{
		"nodeId":           msg.GetHeaderOrDefault(enum.Header_NodeId, ""),
		"nodeName":         nodeName,
		"topologyId":       msg.GetHeaderOrDefault(enum.Header_TopologyId, ""),
		"topologyName":     topologyName,
		"correlationId":    msg.GetHeaderOrDefault(enum.Header_CorrelationId, ""),
		"created":          time.Now(),
		"updated":          time.Now(),
		"type":             typed,
		"returnExchange":   msg.Exchange,
		"returnRoutingKey": msg.RoutingKey,
		"message": bson.M{
			"body":    msg.GetOriginalBody(),
			"headers": msg.Headers,
		},
	}
}
