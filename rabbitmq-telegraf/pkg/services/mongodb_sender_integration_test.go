package services_test

import (
	"context"
	"github.com/stretchr/testify/assert"
	"github.com/stretchr/testify/require"
	"go.mongodb.org/mongo-driver/mongo"
	"go.mongodb.org/mongo-driver/mongo/options"
	"log"
	"rabbitmq-telegraf/pkg/config"
	"rabbitmq-telegraf/pkg/services"
	"testing"
	"time"
)

func TestMongoDbSender_Send(t *testing.T) {
	cl, err := mongo.NewClient(options.Client().ApplyURI(config.MongoDb.DSN))
	if err != nil {
		log.Fatal(err)
	}
	ctx, _ := context.WithTimeout(context.Background(), 5*time.Second)

	err = cl.Connect(ctx)
	require.Nil(t, err)

	coll := cl.Database(config.MongoDb.Database).Collection(config.MongoDb.Collection)
	_, err = coll.DeleteMany(ctx, struct{}{})
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
