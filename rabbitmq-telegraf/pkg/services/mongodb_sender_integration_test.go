package services_test

import (
	"testing"

	"github.com/hanaboso/go-mongodb"
	"github.com/stretchr/testify/assert"
	"github.com/stretchr/testify/require"
	"rabbitmq-telegraf/pkg/config"
	"rabbitmq-telegraf/pkg/services"
)

func TestMongoDbSender_Send(t *testing.T) {
	database := mongodb.Connection{}
	database.Connect(config.MongoDb.DSN)

	coll := database.Database.Collection(config.MongoDb.Collection)
	ctx, cancel := database.Context()
	defer cancel()

	_, err := coll.DeleteMany(ctx, struct{}{})
	require.Nil(t, err)

	msgs := []services.Queue{
		{
			Name:     "queue",
			Messages: 1,
		},
		{
			Name:     "queue2",
			Messages: 12,
		},
	}

	svc := services.NewMongoDbSenderSvc()
	err = svc.Send(msgs)
	require.Nil(t, err)

	c, _ := coll.CountDocuments(ctx, struct{}{})
	assert.Equal(t, int64(2), c)

	_, err = coll.DeleteMany(ctx, struct{}{})
	require.Nil(t, err)
}
