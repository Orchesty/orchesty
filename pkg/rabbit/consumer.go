package rabbit

import (
	"bytes"
	"context"
	"encoding/json"
	"github.com/hanaboso/pipes/counter/pkg/config"
	"github.com/hanaboso/pipes/counter/pkg/model"
	"github.com/rs/zerolog/log"
	"github.com/streadway/amqp"
	"os"
	"time"
)

type Consumer struct {
	Queue   string
	channel *amqp.Channel
	rabbit  *RabbitMq
}

func (c *Consumer) Consume(ctx context.Context) chan *model.ParsedMessage {
	alive := true
	go func() { <-ctx.Done(); alive = false }()

	ch := make(chan *model.ParsedMessage, config.RabbitMq.Prefetch)

	go func() {
		for alive {
			for msg := range c.connect() {
				parsed := c.parseMessage(msg)
				if parsed != nil {
					ch <- parsed
				}
			}
		}
		close(ch)
	}()

	return ch
}

func (c *Consumer) MutliAck(deliveryTag uint64) {
	if err := c.channel.Ack(deliveryTag, true); err != nil {
		log.Fatal().Err(err)
	}
}

func (c *Consumer) connect() <-chan amqp.Delivery {
	for {
		if c.channel != nil {
			_ = c.channel.Close()
		}

		ctx, cancel := context.WithTimeout(context.Background(), 60*time.Second)
		go func() {
			<-ctx.Done()
			if err := ctx.Err(); err == context.DeadlineExceeded {
				// This is hack to work around Go lang's rabbitMq shortcomings
				// It's a fallback due to amqp unresponsiveness
				// For example ex-declare with missing hash-ex plugin will do nothing (no error, timeout, nada)
				log.Error().Err(err).Send()
				os.Exit(1)
			}
		}()

		ch := c.rabbit.channel()
		c.channel = ch
		if err := ch.Qos(config.RabbitMq.Prefetch, 0, false); err != nil {
			log.Error().Err(err).Send()
			cancel()
			continue
		}

		if _, err := ch.QueueDeclare(c.Queue, true, false, false, false, nil); err != nil {
			log.Error().Err(err).Send()
			cancel()
			continue
		}

		delivery, err := ch.Consume(c.Queue, c.Queue, false, false, false, false, nil)
		if err != nil {
			log.Error().Err(err).Send()
			cancel()
			continue
		}

		cancel()
		return delivery
	}
}

func (c *Consumer) stop() {
	if err := c.channel.Close(); err != nil {
		log.Error().Err(err).Send()
	}
}

func (c Consumer) parseMessage(msg amqp.Delivery) *model.ParsedMessage {
	var message model.ProcessMessage

	// Cannot use regular decoder due to process-started timestamp -> it converts int64 to float64
	d := json.NewDecoder(bytes.NewBuffer(msg.Body))
	d.UseNumber()
	if err := d.Decode(&message); err != nil {
		config.Log.Error(err)
		return nil
	}

	var body model.ProcessBody
	if err := json.Unmarshal([]byte(message.Body), &body); err != nil {
		config.Log.Error(err)
		return nil
	}
	message.ProcessBody = body

	return &model.ParsedMessage{
		Headers:        msg.Headers,
		Tag:            msg.DeliveryTag,
		ProcessMessage: message,
	}
}
