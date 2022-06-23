package rabbitmq

import (
	"errors"
	"time"

	"github.com/rs/zerolog/log"
	"github.com/streadway/amqp"
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

			<-time.After(reconnectDelay + time.Duration(retryCount)*time.Second) // TODO If efficiency is a concern, use NewTimer instead and call Timer.Stop if the timer is no longer needed.
			retryCount++
			continue
		}
		retryCount = 0

		c.connection = conn
		notifyClose := conn.NotifyClose(make(chan *amqp.Error))

		// Recreate channels of publishers and consumers
		pubSubs.connect() // TODO: BR2-5 ... při smazání queue se bridge nezvpamatuje -> nevytvoří je znovu

		if err := <-notifyClose; err != nil {
			log.Err(err).Send()
			continue
		}

		log.Debug().Msg("connection gracefully closed")
		return
	}
}
