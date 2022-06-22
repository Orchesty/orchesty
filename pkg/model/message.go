package model

import (
	"fmt"
	"github.com/hanaboso/pipes/counter/pkg/enum"
	"go.mongodb.org/mongo-driver/bson"
	"go.mongodb.org/mongo-driver/mongo"
	"strconv"
	"time"
)

type ProcessMessage struct {
	Body    []byte
	Headers map[string]interface{}
	Tag     uint64
}

type ProcessBody struct {
	Success   bool                   `json:"success"`
	Following int                    `json:"following"`
	Body      string                 `json:"body"`
	Headers   map[string]interface{} `json:"headers"`
}

func (pm ProcessMessage) GetHeader(header enum.HeaderType) (string, error) {
	value, ok := pm.Headers[enum.PrefixHeader(string(header))]
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

func (pm ProcessMessage) GetTimeHeaderOrDefault(header enum.HeaderType) time.Time {
	value, err := pm.GetHeader(header)
	if err != nil {
		return time.Now()
	}

	val, err := strconv.ParseInt(value, 10, 64)
	if err != nil {
		return time.Now()
	}

	return time.Unix(0, val*1_000_000)
}

func (pm ProcessMessage) ProcessInitQuery() mongo.WriteModel {
	doc := mongo.NewUpdateOneModel()
	doc.Filter = bson.M{
		"_id": pm.GetHeaderOrDefault(enum.Header_CorrelationId, ""),
	}
	doc.Update = bson.M{
		"$setOnInsert": bson.M{
			"ok":         0,
			"nok":        0,
			"total":      1,
			"created":    pm.GetTimeHeaderOrDefault(enum.Header_ProcessStarted),
			"topologyId": pm.GetHeaderOrDefault(enum.Header_TopologyId, ""),
			"finished":   nil,
		},
	}
	t := true
	doc.Upsert = &t

	return doc
}

func (pm ProcessMessage) ProcessQuery(body ProcessBody) mongo.WriteModel {
	doc := mongo.NewUpdateOneModel()
	doc.Filter = bson.M{
		"_id": pm.GetHeaderOrDefault(enum.Header_CorrelationId, ""),
	}
	doc.Update = bson.M{
		"$inc": bson.M{
			"ok":    body.successes(),
			"nok":   body.fails(),
			"total": body.Following,
		},
	}

	return doc
}

func (pm ProcessMessage) SubProcessInitQuery() mongo.WriteModel {
	corrId := pm.GetHeaderOrDefault(enum.Header_CorrelationId, "")

	doc := mongo.NewUpdateOneModel()
	doc.Filter = bson.M{
		"_id": pm.GetHeaderOrDefault(enum.Header_ProcessId, ""),
	}
	doc.Update = bson.M{
		"$setOnInsert": bson.M{
			"ok":            0,
			"nok":           0,
			"total":         1,
			"created":       pm.GetTimeHeaderOrDefault(enum.Header_PublishedTimestamp),
			"topologyId":    pm.GetHeaderOrDefault(enum.Header_TopologyId, ""),
			"finished":      nil,
			"correlationId": corrId,
			"parentId":      pm.GetHeaderOrDefault(enum.Header_ParentProcessId, corrId),
		},
	}
	t := true
	doc.Upsert = &t

	return doc
}

func (pm ProcessMessage) SubProcessQuery(body ProcessBody) mongo.WriteModel {
	doc := mongo.NewUpdateOneModel()
	doc.Filter = bson.M{
		"_id": pm.GetHeaderOrDefault(enum.Header_ProcessId, ""),
	}
	doc.Update = bson.M{
		"$inc": bson.M{
			"ok":    body.successes(),
			"nok":   body.fails(),
			"total": body.Following,
		},
	}

	return doc
}

func (pm ProcessMessage) FinishProcessQuery() mongo.WriteModel {
	doc := mongo.NewUpdateOneModel()
	doc.Filter = bson.M{
		"_id":      pm.GetHeaderOrDefault(enum.Header_CorrelationId, ""),
		"finished": nil,
	}
	doc.Update = bson.A{
		bson.M{
			"$set": bson.M{
				"finished": bson.M{
					"$cond": bson.A{
						bson.M{
							"if": bson.M{
								"$eq": bson.A{
									bson.M{
										"$sum": bson.A{"$ok", "$nok"},
									},
									"$total",
								},
							},
						},
						pm.GetTimeHeaderOrDefault(enum.Header_PublishedTimestamp),
						nil,
					},
				},
			},
		},
	}

	return doc
}

// TODO impl for subprocesses later on
func (pm ProcessMessage) FinishSubProcessQuery() mongo.WriteModel {
	doc := mongo.NewUpdateOneModel()
	doc.Filter = bson.M{
		"_id":      pm.GetHeaderOrDefault(enum.Header_ProcessId, ""),
		"finished": nil,
	}
	doc.Update = bson.A{
		bson.M{
			"$set": bson.M{
				"finished": bson.M{
					"$cond": bson.A{
						bson.M{
							"if": bson.M{
								"$eq": bson.A{
									bson.M{
										"$sum": bson.A{"$ok", "$nok"},
									},
									"$total",
								},
							},
						},
						pm.GetTimeHeaderOrDefault(enum.Header_PublishedTimestamp),
						nil,
					},
				},
			},
		},
	}

	return doc
}

func (pm ProcessMessage) ErrorDoc(body ProcessBody) bson.M {
	return bson.M{
		"correlationId": pm.GetHeaderOrDefault(enum.Header_CorrelationId, ""),
		"processId":     pm.GetHeaderOrDefault(enum.Header_ProcessId, ""),
		"body":          body.Body,
		"headers":       body.Headers,
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
