package rabbitmq

import (
	"context"
	"github.com/hanaboso/pipes/bridge/pkg/enum"
	"github.com/hanaboso/pipes/bridge/pkg/utils/timex"
	"sync"
	"time"

	"github.com/hanaboso/pipes/bridge/pkg/model"
	"github.com/rs/zerolog"
	"github.com/rs/zerolog/log"
	"github.com/streadway/amqp"
)

type publisher struct {
	queue           string
	routingKey      string
	exchange        string
	channel         *amqp.Channel
	notifyConfirm   chan amqp.Confirmation
	mu              *sync.Mutex
	lastDeliveryTag uint64
}

type publishers struct {
	client
	publishers []*publisher
	wg         *sync.WaitGroup
}

func (p *publisher) handleReconnect(conn *amqp.Connection, wg *sync.WaitGroup) {
	defer wg.Done()

	var retryCount int
	for !conn.IsClosed() {
		ch, err := conn.Channel()
		if err != nil {
			log.Error().Object(enum.LogHeader_Data, p).Msgf("connecting to channel: %v", err)

			<-time.After(reconnectDelay + time.Duration(retryCount)*time.Second) // TODO If efficiency is a concern, use NewTimer instead and call Timer.Stop if the timer is no longer needed.
			retryCount++
			continue
		}
		retryCount = 0

		if err := ch.Confirm(false); err != nil {
			log.Error().Object(enum.LogHeader_Data, p).Msgf("putting channel to confirm mode: %v", err)
			continue
		}

		p.channel = ch
		p.lastDeliveryTag = 0
		// TODO viz comment in src code -> this should be big enough... in case of dead-lock, try 999
		p.notifyConfirm = p.channel.NotifyPublish(make(chan amqp.Confirmation, 99))

		if p.exchange != "" {
			declareExchange(ch, p.exchange)
			if err := bind(ch, p.queue, p.exchange, p.routingKey); err != nil {
				log.Error().Err(err).Send()
				continue
			}
		}

		if err := <-p.channel.NotifyClose(make(chan *amqp.Error)); err != nil {
			log.Error().Object(enum.LogHeader_Data, p).Msgf("closing channel: %v", err)
			continue
		}

		log.Debug().Object(enum.LogHeader_Data, p).Msg("publisher gracefully closed")
		return
	}
}

func (p *publisher) close() error {
	return p.channel.Close()
}

func newPublishers(shards []model.NodeShard) *publishers {
	var address string
	pubList := make([]*publisher, len(shards))
	for i, shard := range shards {
		if address != "" && address != shard.RabbitMQDSN {
			log.Fatal().Msgf("mismatch of shard addresses [want=%s, got=%s]", address, shard.RabbitMQDSN)
		}
		pubList[i] = &publisher{
			queue:         queue(shard),
			exchange:      exchange(shard),
			routingKey:    routingKey(shard),
			notifyConfirm: make(chan amqp.Confirmation, 1),
			mu:            &sync.Mutex{},
		}
		address = shard.RabbitMQDSN
	}

	pubs := &publishers{
		publishers: pubList,
		wg:         &sync.WaitGroup{},
	}

	go pubs.handleReconnect(pubs, address)
	return pubs
}

func (p *publishers) connect() {
	for _, publisher := range p.publishers {
		p.wg.Add(1)
		go publisher.handleReconnect(p.connection, p.wg)
	}
}

func (p *publishers) close() {
	for _, publisher := range p.publishers {
		if err := publisher.close(); err != nil {
			// TODO what to do if one publisher fails to close?
			log.Error().Object("data", publisher).Msgf("closing rabbitmq publisher: %v", err)
		}
	}

	// Wait for closing all publisher's channels
	p.wg.Wait()

	if err := p.connection.Close(); err != nil {
		// TODO what to do if publishers connection fails to close?
		log.Error().Msgf("closing rabbitmq publisher connection: %v", err)
	}

	log.Info().Msg("gracefully stopped rabbitmq publishers")
}

// Publish will send message and waits for confirmation one by one.
func (p *publisher) Publish(pm amqp.Publishing) error {
	if p.channel == nil {
		return ErrChannelClosed
	}
	/*
		TODO tady to padá po restartu rabbita -> channel není null, ale je closed... po několika chybách se to časem vzpamatuje -> vytváří to však zbytečně duplicitní zpracovávání
		TODO buď počkat, až vše nastartuje -> aktuální ohejbák AwaitStartup() je použit při startu bridge
		TODO nebo čekat na úrovní workera např.?

			{"level":"error","service":"bridge","trace":[{"function":"bridge.(*node).process","file":"/app/pkg/bridge/worker.go:77"},{"function":"bridge.","file":"/app/pkg/bridge/worker.go:58"}],"message":"Exception (504) Reason: \"channel/connection is not open\"","nodeId":"node1","topologyId":"topo","timestamp":1625649064}
	*/

	p.mu.Lock()
	defer p.mu.Unlock()

	pm.Headers[model.Prefix(enum.Header_PublishedTimestamp)] = timex.UnixMs()
	if err := p.channel.Publish(p.exchange, p.routingKey, false, false, pm); err != nil {
		return err
	}

	ctx, cancel := context.WithTimeout(context.Background(), publishTimeout)
	defer cancel()

	for {
		select {
		case confirm, ok := <-p.notifyConfirm:
			if !ok {
				return ErrChannelClosed
			}
			if confirm.DeliveryTag < p.lastDeliveryTag+1 {
				log.Warn().Msgf("Received unexpected delivery tag [want=%d, got=%d]", p.lastDeliveryTag+1, confirm.DeliveryTag)
				continue
			}
			p.lastDeliveryTag = confirm.DeliveryTag
			if !confirm.Ack {
				return ErrPublishUnconfirmed
			}
			return nil
		case <-ctx.Done():
			return ErrPublishTimedOut
		}
	}
}

// Adds exchange & routingKey - use as .Object("data", p)
func (p publisher) MarshalZerologObject(e *zerolog.Event) {
	e.Str("exchange", p.exchange)
	e.Str("routingKey", p.routingKey)
}
