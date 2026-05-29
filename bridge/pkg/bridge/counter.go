package bridge

import (
	"encoding/json"
	"fmt"
	"github.com/hanaboso/pipes/bridge/pkg/bridge/types"
	"github.com/hanaboso/pipes/bridge/pkg/enum"
	"github.com/hanaboso/pipes/bridge/pkg/model"
	"github.com/hanaboso/pipes/bridge/pkg/rabbit"
	"github.com/hanaboso/pipes/bridge/pkg/utils/timex"
	amqp "github.com/rabbitmq/amqp091-go"
	"github.com/rs/zerolog/log"
)

type counter struct {
	publisher types.Publisher
}

type counterMessageBody struct {
	Success   bool                   `json:"success"`
	Following int                    `json:"following"`
	Body      string                 `json:"body"`
	Headers   map[string]interface{} `json:"headers"`
}

func (c counter) send(result model.ProcessResult, followers int) {
	msg := result.Message()
	status := result.Status()

	body := counterMessageBody{
		Following: followers,
		Success:   true,
	}

	if status == enum.ProcessStatus_Error || status == enum.ProcessStatus_Trash {
		body.Success = false

		err := result.Error()
		if err == nil {
			err = fmt.Errorf("failed")
		} // TODO tohle by tu technicky nemělo být potřeba -> má se posílat ze zprávy, ne od counter
		log.Error().EmbedObject(msg).Err(err).Send()
	}

	// TODO co se stane při výpadku v polovině batche? Potřeba vyřešit multi-counter
	// Error is redelivery -> the message will be repeated
	if status == enum.ProcessStatus_Error {
		followers = 1
	}

	bodyString, _ := json.Marshal(body)
	msgDto := model.MessageDto{
		Headers: msg.Headers,
		Body:    string(bodyString),
	}
	msgDtoBytes, _ := json.Marshal(msgDto)
	published := msg.Published
	if published <= 0 {
		published = timex.UnixMs()
	}

	// TODO prozatím se ignoruje non-delivery, dokud se nevyřeší couter do funkční podoby
	_ = c.publisher.Publish(amqp.Publishing{
		ContentType: "application/json",
		Body:        msgDtoBytes,
		Headers: map[string]interface{}{
			enum.Header_PublishedTimestamp: published,
		},
	})
}

func newCounter(rabbitContainer rabbit.Container) counter {
	return counter{
		publisher: rabbitContainer.Counter,
	}
}
