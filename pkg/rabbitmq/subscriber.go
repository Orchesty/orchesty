package rabbitmq

import (
	"encoding/json"
	"fmt"
	"github.com/hanaboso/pipes/bridge/pkg/utils/timex"
	"sync"
	"time"

	"github.com/hanaboso/pipes/bridge/pkg/enum"

	"github.com/hanaboso/pipes/bridge/pkg/model"
	amqp "github.com/rabbitmq/amqp091-go"
	"github.com/rs/zerolog"
	"github.com/rs/zerolog/log"
)

type subscriber struct {
	queue      string
	exchange   string
	routingKey string
	channel    *amqp.Channel
	delivery   chan<- *model.ProcessMessage
	prefetch   int
}

type subscribers struct {
	client
	address     string
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
		notifyCancel := s.channel.NotifyCancel(make(chan string))

		declareQueue(s.channel, s.queue)
		if err := bind(ch, s.queue, s.exchange, s.routingKey); err != nil {
			log.Error().Err(err).Send()
			continue
		}

		prefetch := 1
		if s.prefetch > 0 {
			prefetch = s.prefetch
		}

		// TODO: BR2-6
		if err := s.channel.Qos(prefetch, 0, false); err != nil {
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
		select {
		case closed := <-notifyClose:
			if closed != nil {
				log.Error().Object(enum.LogHeader_Data, s).Msgf("closing channel: %v", closed)
				continue
			}
		case cancelled := <-notifyCancel:
			log.Error().Object(enum.LogHeader_Data, s).Msgf("removed: %v", cancelled)
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
		if parsed := s.parseMessage(msg, wg); parsed != nil {
			s.delivery <- parsed
		}
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
			exchange:   exchange(shard),
			queue:      queue(shard),
			routingKey: routingKey(shard),
			delivery:   shard.Node.Messages,
			prefetch:   shard.Node.Settings.Bridge.Prefetch,
		}
		addr = shard.RabbitMQDSN
	}

	subs := &subscribers{
		address:     addr,
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
	// TODO v nějakém případě nedošlo k Ack ani Nack a zpráva ostala viset -> stalo se po restartu rabbita, ale nedaří se mi to úspěšně nasimulovat
	ackFn := func() error {
		defer wg.Done()

		return msg.Ack(false)
	}

	nackFn := func() error {
		defer wg.Done()

		return msg.Nack(false, true)
	}

	var fullBody model.MessageDto
	if err := json.Unmarshal(msg.Body, &fullBody); err != nil {
		log.Err(err).EmbedObject(s).Send()
		_ = ackFn()
		return nil
	}

	published, _ := msg.Headers[enum.Header_PublishedTimestamp].(int64)

	dto := model.ProcessMessage{
		Body:           []byte(fullBody.Body),
		Headers:        fullBody.Headers,
		Ack:            ackFn,
		Nack:           nackFn,
		Published:      published,
		ProcessStarted: timex.UnixMs(),
		Status:         enum.MessageStatus_Received,
		Exchange:       msg.Exchange,
		RoutingKey:     msg.RoutingKey,
	}

	if limit, err := dto.GetHeader(enum.Header_LimitKeyBase); err == nil {
		dto.SetHeader(enum.Header_LimitKey, limit)
		dto.DeleteHeader(enum.Header_LimitKeyBase)
	}

	return &dto
}

// Adds queue - use as .Object("data", s)
func (s subscriber) MarshalZerologObject(e *zerolog.Event) {
	e.Str("queue", s.queue)
}
