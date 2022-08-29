package rabbitmq

import (
	"errors"
	"github.com/hanaboso/pipes/bridge/pkg/utils/intx"
	"time"

	amqp "github.com/rabbitmq/amqp091-go"
	"github.com/rs/zerolog/log"
)

const (
	// When reconnecting to the server after connection failure
	reconnectDelay = 10 * time.Second
	// When resending messages the server didn't confirm
	publishTimeout = 10 * time.Second
)

var (
	ErrPublishTimedOut    = errors.New("rabbitmq publish timed out")
	ErrPublishUnconfirmed = errors.New("rabbitmq publish unconfirmed")
	ErrChannelClosed      = errors.New("rabbitmq channel closed")
)

type connector interface {
	connect()
}

// client holds necessary information for rabbitMQ
type client struct {
	connection  *amqp.Connection
	notifyClose chan *amqp.Error
}

// handleReconnect will wait for a connection error on
// notifyClose, and then continuously attempt to reconnect.
func (c *client) handleReconnect(pubSubs connector, addr string) {
	var retryCount int
	for {
		log.Printf("attempting to connect to rabbitMQ: %s", addr)

		conn, err := amqp.Dial(addr)
		if err != nil {
			log.Printf("failed connecting to RabbitMQ server: %v", err)

			<-time.After(reconnectDelay + time.Duration(intx.Max(retryCount, 30))*time.Second) // TODO If efficiency is a concern, use NewTimer instead and call Timer.Stop if the timer is no longer needed.
			retryCount++
			continue
		}
		retryCount = 0

		c.connection = conn
		notifyClose := conn.NotifyClose(make(chan *amqp.Error))

		// Recreate channels of publishers and consumers
		pubSubs.connect()

		if err := <-notifyClose; err != nil {
			log.Err(err).Send()
			continue
		}

		log.Debug().Msg("connection gracefully closed")
		return
	}
}
