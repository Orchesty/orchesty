package mongo

import (
	"errors"
	"github.com/hanaboso/pipes/bridge/pkg/model"
	"github.com/stretchr/testify/assert"
	"github.com/stretchr/testify/require"
	"go.mongodb.org/mongo-driver/bson"
	"testing"
)

type message struct {
	Id string `bson:"_id"`
	// Fields used for filtering are separated should we alter headers and make them as a part of message' body
	CorrelationId    string
	NodeId           string
	TopologyId       string
	Type             string
	ReturnExchange   string
	ReturnRoutingKey string
	Message          messageData
}

type messageData struct {
	Body    string
	Headers map[string]interface{}
}

func TestMongoDb_StoreUserTask(t *testing.T) {
	mongo := NewMongoDb()
	_ = mongo.collection.Drop(nil)

	dto := prepareDto()
	_, err := mongo.StoreUserTask(dto.Ok(), "", "")
	require.Nil(t, err)

	res, err := mongo.collection.Find(nil, bson.M{})
	require.Nil(t, err)

	var docs []message
	err = res.All(nil, &docs)
	require.Nil(t, err)

	assert.Equal(t, "losos", docs[0].Message.Body)
	assert.Equal(t, Type_UserTask, docs[0].Type)
	assert.Equal(t,
		map[string]interface{}{
			"int":    "666",
			"string": "string",
		},
		docs[0].Message.Headers,
	)

	defer res.Close(nil)
}

func TestMongoDb_StoreUserTaskError(t *testing.T) {
	mongo := NewMongoDb()
	_ = mongo.collection.Drop(nil)

	dto := prepareDto()
	_, err := mongo.StoreUserTask(dto.Error(errors.New("go is shit")), "", "")
	require.Nil(t, err)

	res, err := mongo.collection.Find(nil, bson.M{})
	require.Nil(t, err)

	var docs []message
	err = res.All(nil, &docs)
	require.Nil(t, err)

	assert.Equal(t, "losos", docs[0].Message.Body)
	assert.Equal(t, Type_Trash, docs[0].Type)
	assert.Equal(t,
		map[string]interface{}{
			"int":            "666",
			"result-message": "go is shit",
			"string":         "string",
		},
		docs[0].Message.Headers,
	)

	defer res.Close(nil)
}

func prepareDto() model.ProcessMessage {
	dto := model.ProcessMessage{}
	dto.Body = []byte("losos")
	dto.SetHeader("string", "string")
	dto.SetHeader("int", "666")

	return dto
}
