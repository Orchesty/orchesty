package rabbit

import (
	"context"
	"github.com/hanaboso/pipes/counter/pkg/config"
	"github.com/hanaboso/pipes/counter/pkg/enum"
	"github.com/hanaboso/pipes/counter/pkg/model"
	"github.com/rs/zerolog/log"
	"github.com/streadway/amqp"
	"os"
	"strings"
	"time"
)

type Consumer struct {
	Queue   string
	channel *amqp.Channel
	rabbit  *RabbitMq
}

func (c *Consumer) Consume(ctx context.Context) chan *model.ProcessMessage {
	alive := true
	go func() { <-ctx.Done(); alive = false }()

	ch := make(chan *model.ProcessMessage, config.RabbitMq.Prefetch)

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

func (c Consumer) parseMessage(msg amqp.Delivery) *model.ProcessMessage {
	pfHeaders := map[string]interface{}{}
	for key, value := range msg.Headers {
		if strings.HasPrefix(key, enum.HeaderPrefix) {
			pfHeaders[key] = value
		}
	}

	return &model.ProcessMessage{
		Body:    msg.Body,
		Headers: pfHeaders,
		Tag:     msg.DeliveryTag,
	}
}
