package model

import (
	"fmt"
	"github.com/hanaboso/pipes/counter/pkg/enum"
	"go.mongodb.org/mongo-driver/bson"
	"go.mongodb.org/mongo-driver/mongo"
	"strconv"
	"time"
)

type ParsedMessage struct {
	Headers        map[string]interface{} // RabbitMq headers -> contains only published timestamp
	ProcessMessage ProcessMessage         // Counter message body containing body&headers
	Tag            uint64
	Ok             bool
}

type ProcessMessage struct {
	Headers     map[string]interface{} `json:"headers"` // Message headers required for counter message
	Body        string                 `json:"body"`    // encoded ProcessBody
	ProcessBody ProcessBody            `json:"-"`
}

type ProcessBody struct {
	Success   bool                   `json:"success"`
	Following int                    `json:"following"`
	Body      string                 `json:"body"`    // Message resulting in error in success=false case
	Headers   map[string]interface{} `json:"headers"` // Message resulting in error in success=false case
}

func (pm ParsedMessage) getHeader(header enum.HeaderType) (string, error) {
	value, ok := pm.Headers[string(header)]
	if !ok {
		return "", fmt.Errorf("requested header [%s] does not exist", header)
	}

	return fmt.Sprint(value), nil
}

func (pm ProcessMessage) GetHeader(header enum.HeaderType) (string, error) {
	value, ok := pm.Headers[string(header)]
	if !ok {
		return "", fmt.Errorf("requested header [%s] does not exist", header)
	}

	return fmt.Sprint(value), nil
}

func (pm ProcessMessage) GetHeaderOrDefault(header enum.HeaderType, defaultValue string) string {
	value, err := pm.GetHeader(header)
	if err != nil {
		return defaultValue
	}

	return value
}

func (pm ProcessMessage) GetBoolHeaderOrDefault(header enum.HeaderType, defaultValue bool) bool {
	value, err := pm.GetHeader(header)
	if err != nil {
		return defaultValue
	}

	return value == "true" || value == "1"
}

func (pm ParsedMessage) GetPublishedTimestamp() time.Time {
	value, err := pm.getHeader(enum.Header_PublishedTimestamp)
	if err != nil {
		return time.Now()
	}

	val, err := strconv.ParseInt(value, 10, 64)
	if err != nil {
		return time.Now()
	}

	return time.Unix(0, val*1_000_000)
}

func (pm ProcessMessage) GetTimeHeaderOrDefault(header enum.HeaderType) time.Time {
	value, err := pm.GetHeader(header)
	if err != nil {
		return time.Now()
	}

	val, err := strconv.ParseUint(value, 10, 64)
	if err != nil {
		return time.Now()
	}

	return time.Unix(0, int64(val*1_000_000))
}

func (pm ParsedMessage) ProcessInitQuery() mongo.WriteModel {
	var user *string
	if tmp := pm.ProcessMessage.GetHeaderOrDefault(enum.Header_User, ""); tmp != "" {
		user = &tmp
	}

	doc := mongo.NewUpdateOneModel()
	doc.Filter = bson.M{
		"_id": pm.ProcessMessage.GetHeaderOrDefault(enum.Header_CorrelationId, ""),
	}
	doc.Update = bson.M{
		"$setOnInsert": bson.M{
			"ok":          0,
			"nok":         0,
			"total":       1,
			"created":     pm.ProcessMessage.GetTimeHeaderOrDefault(enum.Header_ProcessStarted),
			"topologyId":  pm.ProcessMessage.GetHeaderOrDefault(enum.Header_TopologyId, ""),
			"user":        user,
			"finished":    nil,
			"systemEvent": pm.ProcessMessage.GetBoolHeaderOrDefault(enum.Header_SystemEvent, false),
		},
	}
	t := true
	doc.Upsert = &t

	return doc
}

func (pm ParsedMessage) ProcessQuery() mongo.WriteModel {
	doc := mongo.NewUpdateOneModel()
	doc.Filter = bson.M{
		"_id": pm.ProcessMessage.GetHeaderOrDefault(enum.Header_CorrelationId, ""),
	}
	doc.Update = bson.M{
		"$inc": bson.M{
			"ok":    pm.ProcessMessage.ProcessBody.successes(),
			"nok":   pm.ProcessMessage.ProcessBody.fails(),
			"total": pm.ProcessMessage.ProcessBody.Following,
		},
	}

	return doc
}

func (pm ParsedMessage) SubProcessInitQuery() mongo.WriteModel {
	corrId := pm.ProcessMessage.GetHeaderOrDefault(enum.Header_CorrelationId, "")

	doc := mongo.NewUpdateOneModel()
	doc.Filter = bson.M{
		"_id": pm.ProcessMessage.GetHeaderOrDefault(enum.Header_ProcessId, ""),
	}
	doc.Update = bson.M{
		"$setOnInsert": bson.M{
			"ok":            0,
			"nok":           0,
			"total":         1,
			"created":       pm.GetPublishedTimestamp(),
			"topologyId":    pm.ProcessMessage.GetHeaderOrDefault(enum.Header_TopologyId, ""),
			"finished":      nil,
			"correlationId": corrId,
			"parentId":      pm.ProcessMessage.GetHeaderOrDefault(enum.Header_ParentProcessId, corrId),
			"systemEvent":   pm.ProcessMessage.GetBoolHeaderOrDefault(enum.Header_SystemEvent, false),
		},
	}
	t := true
	doc.Upsert = &t

	return doc
}

func (pm ParsedMessage) SubProcessQuery() mongo.WriteModel {
	doc := mongo.NewUpdateOneModel()
	doc.Filter = bson.M{
		"_id": pm.ProcessMessage.GetHeaderOrDefault(enum.Header_ProcessId, ""),
	}
	doc.Update = bson.M{
		"$inc": bson.M{
			"ok":    pm.ProcessMessage.ProcessBody.successes(),
			"nok":   pm.ProcessMessage.ProcessBody.fails(),
			"total": pm.ProcessMessage.ProcessBody.Following,
		},
	}

	return doc
}

func (pm ParsedMessage) FinishProcessQuery() mongo.WriteModel {
	doc := mongo.NewUpdateOneModel()
	doc.Filter = bson.M{
		"_id": pm.ProcessMessage.GetHeaderOrDefault(enum.Header_CorrelationId, ""),
	}
	doc.Update = bson.A{
		bson.M{
			"$set": bson.M{
				"finished": bson.M{
					"$cond": bson.A{
						bson.M{
							"$gte": bson.A{
								bson.M{
									"$sum": bson.A{"$ok", "$nok"},
								},
								"$total",
							},
						},
						pm.GetPublishedTimestamp(),
						nil,
					},
				},
			},
		},
	}

	return doc
}

// TODO impl for subprocesses later on
func (pm ParsedMessage) FinishSubProcessQuery() mongo.WriteModel {
	doc := mongo.NewUpdateOneModel()
	doc.Filter = bson.M{
		"_id":      pm.ProcessMessage.GetHeaderOrDefault(enum.Header_ProcessId, ""),
		"finished": nil,
	}
	doc.Update = bson.A{
		bson.M{
			"$set": bson.M{
				"finished": bson.M{
					"$cond": bson.A{
						bson.M{
							"$eq": bson.A{
								bson.M{
									"$sum": bson.A{"$ok", "$nok"},
								},
								"$total",
							},
						},
						pm.GetPublishedTimestamp(),
						nil,
					},
				},
			},
		},
	}

	return doc
}

func (pm ParsedMessage) ErrorDoc() bson.M {
	return bson.M{
		"correlationId": pm.ProcessMessage.GetHeaderOrDefault(enum.Header_CorrelationId, ""),
		"processId":     pm.ProcessMessage.GetHeaderOrDefault(enum.Header_ProcessId, ""),
		"body":          pm.ProcessMessage.ProcessBody.Body,
		"headers":       pm.ProcessMessage.ProcessBody.Headers,
		"created":       time.Now(),
	}
}

func (pb ProcessBody) successes() int {
	if pb.Success {
		return 1
	}

	return 0
}

func (pb ProcessBody) fails() int {
	if pb.Success {
		return 0
	}

	return 1
}
