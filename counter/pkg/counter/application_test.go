package counter

import (
	"context"
	"fmt"
	"github.com/hanaboso/pipes/counter/pkg/config"
	"github.com/hanaboso/pipes/counter/pkg/model"
	"github.com/hanaboso/pipes/counter/pkg/mongo"
	"github.com/hanaboso/pipes/counter/pkg/rabbit"
	"github.com/streadway/amqp"
	"github.com/stretchr/testify/assert"
	"go.mongodb.org/mongo-driver/bson"
	mongodb "go.mongodb.org/mongo-driver/mongo"
	"go.mongodb.org/mongo-driver/mongo/options"
	"testing"
	"time"
)

// Because it's easier to make test with real db, then to try to mock it without bending the code in this Shitty language
// Also -> keep it sync... go is too shitty to handle parallel dbs in any reasonable way
// In case of error, first check that rabbit & mongo is clean

func TestMultiCounter_ok(t *testing.T) {
	type test struct {
		messages []amqp.Publishing
		result   string
	}
	tests := []test{
		{
			messages: []amqp.Publishing{
				message("c12", 1, true),
				message("c12", 1, true),
				message("c12", 0, true),
			},
			result: `{"process_id":"c12","success":true}`,
		},
		{
			messages: []amqp.Publishing{
				message("c13", 1, true),
				message("c13", 1, false),
				message("c13", 0, true),
			},
			result: `{"process_id":"c13","success":false}`,
		},
		{
			messages: []amqp.Publishing{
				message("c155", 1, true),
				message("c155", 2, true),
				message("c155", 2, true),
				message("c155", 1, true),
				message("c155", 0, true),
				message("c155", 0, true),
				message("c155", 0, true),
			},
			result: `{"process_id":"c155","success":true}`,
		},
		{
			messages: []amqp.Publishing{
				message("c165", 1, true),
				message("c165", 2, false),
				message("c165", 1, true),
				message("c165", 0, true),
				message("c165", 0, true),
			},
			result: `{"process_id":"c165","success":false}`,
		},
	}

	rabbitMq := rabbit.NewRabbitMq()
	mongoDb := mongo.NewMongo()
	counter := NewMultiCounter(rabbitMq, mongoDb)

	pub := rabbitMq.NewPublisher("", "pipes.multi-counter")
	status := rabbitMq.NewConsumer("pipes.results")

	ctx, cancel := context.WithCancel(context.Background())
	go counter.Start(ctx)
	results := status.Consume(ctx)

	for _, tt := range tests {
		for _, msg := range tt.messages {
			pub.Publish(msg)
		}
		res := <-results
		status.MutliAck(res.Tag)
		resBody := string(res.Body)
		assert.Equal(t, tt.result, resBody)
	}

	time.Sleep(100 * time.Millisecond)
	cancel()
	rabbitMq.Stop()
}

func TestMultiCounter_nok(t *testing.T) {
	// In case of error, first check that rabbit is clean
	rabbitMq := rabbit.NewRabbitMq()
	mongoDb := mongo.NewMongo()
	counter := NewMultiCounter(rabbitMq, mongoDb)

	pub := rabbitMq.NewPublisher("", "pipes.multi-counter")

	dsn := config.MongoDb.Dsn
	mongoCl, _ := mongodb.NewClient(
		options.
			Client().
			ApplyURI(dsn).
			SetMaxPoolSize(10),
	)
	ctx, cancel := context.WithCancel(context.Background())
	_ = mongoCl.Connect(ctx)
	coll := mongoCl.Database("test").Collection(config.MongoDb.CounterCollection)
	_ = coll.Drop(ctx)

	pub.Publish(message("unfinished", 1, false))
	pub.Publish(message("unfinished", 2, true))
	pub.Publish(message("un-helper", 0, true))
	go counter.Start(ctx)

	status := rabbitMq.NewConsumer("pipes.results")
	results := status.Consume(ctx)
	waiter := <-results
	status.MutliAck(waiter.Tag)
	resBody := string(waiter.Body)
	assert.Equal(t, `{"process_id":"un-helper","success":true}`, resBody)

	time.Sleep(100 * time.Millisecond)
	var res model.Process
	_ = coll.FindOne(ctx, bson.M{}).Decode(&res)

	exp := model.Process{
		CorrelationId: "unfinished",
		Ok:            1,
		Nok:           1,
		Created:       res.Created,
		Total:         4,
		Subprocesses:  make(map[string]*model.Subprocess, 0),
	}
	assert.Equal(t, exp, res)
	cancel()
	rabbitMq.Stop()
}

func message(correlationId string, following int, ok bool) amqp.Publishing {
	return amqp.Publishing{
		ContentType: "application/json",
		Headers: map[string]interface{}{
			"pf-correlation-id": correlationId,
		},
		Body: []byte(fmt.Sprintf(`{"following": %d, "success": %t}`, following, ok)),
	}
}
