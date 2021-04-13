package rabbitmq

import (
	"fmt"
	"sync"
	"time"

	"github.com/hanaboso/pipes/bridge/pkg/enum"

	"github.com/hanaboso/pipes/bridge/pkg/model"
	"github.com/rs/zerolog"
	"github.com/rs/zerolog/log"
	"github.com/streadway/amqp"
)

type subscriber struct {
	queue    string
	channel  *amqp.Channel
	delivery chan<- *model.ProcessMessage
}

type subscribers struct {
	client
	subscribers []*subscriber
	wg          *sync.WaitGroup
}

func (s *subscriber) handleReconnect(conn *amqp.Connection, wg *sync.WaitGroup) {
	defer wg.Done() // 4. Notify subscribes that this subscriber is done

	var retryCount int
	for !conn.IsClosed() {
		ch, err := conn.Channel()
		if err != nil {
			log.Error().Object(enum.LogHeader_Data, s).Msgf("connecting to channel: %v", err)

			<-time.After(reconnectDelay + time.Duration(retryCount)*time.Second) // TODO If efficiency is a concern, use NewTimer instead and call Timer.Stop if the timer is no longer needed.
			retryCount++
			continue
		}
		retryCount = 0

		s.channel = ch
		notifyClose := s.channel.NotifyClose(make(chan *amqp.Error))

		// TODO: BR2-6
		if err := s.channel.Qos(1, 0, false); err != nil {
			log.Error().Object(enum.LogHeader_Data, s).Msgf("setting qos: %v", err)
			continue
		}

		// TODO: BR2-6
		msgs, err := s.channel.Consume(
			s.queue,
			s.consumerName(),
			false,
			true,
			false,
			false,
			nil,
		)
		if err != nil {
			log.Error().Object(enum.LogHeader_Data, s).Msgf("consuming channel: %v", err)
			continue
		}

		go s.consume(msgs)

		// Graceful notifyClose comes from consume function (after consuming is cancelled and workers are done)
		// TODO Add context with timeout?
		if err := <-notifyClose; err != nil {
			// Error shutdown routine
			log.Error().Object(enum.LogHeader_Data, s).Msgf("closing channel: %v", err)
			continue
		}

		// Graceful shutdown routine
		close(s.delivery) // 3. Close worker channel

		log.Debug().Object(enum.LogHeader_Data, s).Msg("subscriber gracefully closed")
		return
	}
}

func (s *subscriber) consume(msgs <-chan amqp.Delivery) {
	wg := &sync.WaitGroup{}
	for msg := range msgs {
		wg.Add(1)
		s.delivery <- s.parseMessage(msg, wg)
	}

	wg.Wait() // 1. Await for (n)acking of messages that have been sent to process

	if err := s.channel.Close(); err != nil { // 2. Close rabbit channel
		log.Error().Object(enum.LogHeader_Data, s).Msgf("closing consumer: %v", err)
	}
}

func (s *subscriber) close() error {
	return s.channel.Cancel(s.consumerName(), false)
}

func (s *subscriber) consumerName() string {
	return fmt.Sprintf("consumer-%s", s.queue)
}

// newSubscribers creates container for subscribers sharing one RabbitMQ connection
func newSubscribers(shards []model.NodeShard) *subscribers {
	var addr string
	ss := make([]*subscriber, len(shards))
	for i, shard := range shards {
		if addr != "" && addr != shard.RabbitMQDSN {
			log.Fatal().Msgf("mismatch of shard addresses [want=%s, got=%s]", addr, shard.RabbitMQDSN)
		}
		ss[i] = &subscriber{
			queue:    queue(shard),
			delivery: shard.Node.Messages,
		}
		addr = shard.RabbitMQDSN
	}

	subs := &subscribers{
		subscribers: ss,
		wg:          &sync.WaitGroup{},
	}

	go subs.handleReconnect(subs, addr)
	return subs
}

func (s *subscribers) connect() {
	for _, subscriber := range s.subscribers {
		s.wg.Add(1)
		go subscriber.handleReconnect(s.connection, s.wg)
	}
}

func (s *subscribers) close() {
	// Cancel consuming of all subscribers
	for _, subscriber := range s.subscribers {
		// Error is not returned to make sure all subscribers are called to close
		if err := subscriber.close(); err != nil {
			// TODO what to do if one subscriber fails to close?
			log.Error().Msgf("closing rabbitmq consumer: %v", err)
		}
	}

	// Wait for closing all subscriber's channels
	s.wg.Wait()

	// Close connection
	if err := s.connection.Close(); err != nil {
		// TODO what to do if subscribers connection fails to close?
		log.Error().Msgf("closing rabbitmq consumers connection: %v", err)
	}

	log.Info().Msg("gracefully stopped rabbitmq subscribers")
}

func (s *subscriber) parseMessage(msg amqp.Delivery, wg *sync.WaitGroup) *model.ProcessMessage {
	ackFn := func() error {
		defer wg.Done()
		if err := msg.Ack(false); err != nil {
			return err
		}
		log.Info().EmbedObject(s).Msg("Ack")
		return nil
	}

	nackFn := func() error {
		defer wg.Done()
		if err := msg.Nack(false, true); err != nil {
			return err
		}
		log.Info().EmbedObject(s).Msg("Nack")
		return nil
	}

	return &model.ProcessMessage{
		Body:    msg.Body,
		Headers: msg.Headers,
		Ack:     ackFn,
		Nack:    nackFn,
	}
}

// Adds queue - use as .Object("data", s)
func (s subscriber) MarshalZerologObject(e *zerolog.Event) {
	e.Str("queue", s.queue)
}
