package router

import (
	"fmt"
	"github.com/hanaboso/go-utils/pkg/contextx"
	"github.com/stretchr/testify/assert"
	"go.mongodb.org/mongo-driver/bson"
	"limiter/pkg/config"
	"limiter/pkg/model"
	"limiter/pkg/mongo"
	"limiter/pkg/router"
	"net/http"
	"testing"
)

func TestTerminate(t *testing.T) {
	tests := []struct {
		route     string
		remaining int
	}{
		{"limit-key/limit1;1;1", 2},
		{"topology-id/1", 0},
		{"correlation-id/1", 3},
	}

	mongoSvc := mongo.NewMongoSvc()
	mongoSvc.Connection().Database.Collection(config.MongoDb.ApiTokenCollection).InsertOne(
		contextx.WithTimeoutSecondsCtx(10),
		map[string]interface{}{"user": "orchesty", "key": "asd"},
	)

	for _, test := range tests {
		_ = mongoSvc.ClearAll()
		prepareMessages(mongoSvc)
		server := router.Router(router.Container{
			Mongo: mongoSvc,
		})

		request, _ := http.NewRequest(http.MethodDelete, fmt.Sprintf("/terminate/%s", test.route), nil)
		request.Header.Set("orchesty-api-key", "asd")
		server.ServeHTTP(responseMock{}, request)

		ctx := contextx.WithTimeoutSecondsCtx(30)
		result, _ := mongoSvc.Collection().Find(ctx, bson.D{})

		var messages []mongo.Message
		_ = result.All(ctx, &messages)
		assert.Equal(t, test.remaining, len(messages))
	}
}

func prepareMessages(mongoSvc mongo.MongoSvc) {
	_ = mongoSvc.Insert(mongo.Message{
		LimitKey: "limit1;1;1",
		Message: &model.MessageDto{
			Headers: map[string]interface{}{
				"topology-id":    "1",
				"correlation-id": "1",
			},
		},
	})
	_ = mongoSvc.Insert(mongo.Message{
		LimitKey: "limit2;1;1",
		Message: &model.MessageDto{
			Headers: map[string]interface{}{
				"topology-id":    "1",
				"correlation-id": "2",
			},
		},
	})
	_ = mongoSvc.Insert(mongo.Message{
		LimitKey: "limit1;1;1",
		Message: &model.MessageDto{
			Headers: map[string]interface{}{
				"topology-id":    "1",
				"correlation-id": "3",
			},
		},
	})
	_ = mongoSvc.Insert(mongo.Message{
		LimitKey: "limit2;1;1",
		Message: &model.MessageDto{
			Headers: map[string]interface{}{
				"topology-id":    "1",
				"correlation-id": "4",
			},
		},
	})
}
