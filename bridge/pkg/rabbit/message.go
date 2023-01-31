package rabbit

import (
	"encoding/json"
	"github.com/hanaboso/pipes/bridge/pkg/enum"
	"github.com/hanaboso/pipes/bridge/pkg/model"
	"github.com/hanaboso/pipes/bridge/pkg/utils/timex"
	amqp "github.com/rabbitmq/amqp091-go"
	"github.com/rs/zerolog/log"
	"sync"
)

func ParseMessage(msg amqp.Delivery, wg *sync.WaitGroup) *model.ProcessMessage {
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
		log.Err(err).Send()
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
