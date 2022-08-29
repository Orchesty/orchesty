package rabbit

import (
	"context"
	"errors"
	"github.com/hanaboso/pipes/counter/pkg/config"
	amqp "github.com/rabbitmq/amqp091-go"
	"os"
	"time"
)

type Publisher struct {
	Exchange      string
	RoutingKey    string
	channel       *amqp.Channel
	rabbit        *RabbitMq
	notifyConfirm chan amqp.Confirmation
}

// Keep it synced or add Lock
func (p *Publisher) Publish(msg amqp.Publishing) {
	for {
		if err := p.channel.Publish(p.Exchange, p.RoutingKey, false, false, msg); err != nil {
			config.Log.Error(err)
			p.connect()
			continue
		}

		ctx, cancel := context.WithTimeout(context.Background(), 30*time.Second)
		select {
		case confirm, ok := <-p.notifyConfirm:
			if !ok {
				config.Log.Error(errors.New("publisher's channel is closed"))
				p.connect()
				cancel()
				continue
			}
			if !confirm.Ack {
				config.Log.Error(errors.New("delivery nack"))
				p.connect()
				cancel()
				continue
			}
			cancel()
			return
		case <-ctx.Done():
			config.Log.Error(errors.New("publish timeout"))
			continue
		}
	}
}

func (p *Publisher) connect() {
	for {
		if p.channel != nil {
			_ = p.channel.Close()
		}

		ch := p.rabbit.channel()
		p.channel = ch

		ctx, cancel := context.WithTimeout(context.Background(), 60*time.Second)
		go func() {
			<-ctx.Done()
			if err := ctx.Err(); err == context.DeadlineExceeded {
				// This is hack to work around Go lang's rabbitMq shortcomings
				// It's a fallback due to amqp unresponsiveness
				// For example ex-declare with missing hash-ex plugin will do nothing (no error, timeout, nada)
				config.Log.Error(err)
				os.Exit(1)
			}
		}()

		if _, err := ch.QueueDeclare(p.RoutingKey, true, false, false, false, nil); err != nil {
			config.Log.Error(err)
			cancel()
			continue
		}

		_ = ch.Confirm(false)
		p.notifyConfirm = ch.NotifyPublish(make(chan amqp.Confirmation, 20))

		cancel()
		return
	}
}

func (p *Publisher) stop() {
	if p.channel != nil {
		if err := p.channel.Close(); err != nil {
			config.Log.Error(err)
		}
	}
}
