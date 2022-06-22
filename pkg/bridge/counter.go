package bridge

import (
	"encoding/json"
	"fmt"
	"github.com/hanaboso/pipes/bridge/pkg/bridge/types"
	"github.com/hanaboso/pipes/bridge/pkg/enum"
	"github.com/hanaboso/pipes/bridge/pkg/model"
	"github.com/rs/zerolog/log"
	"github.com/streadway/amqp"
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
		body.Body = string(result.Message().Body)
		body.Headers = result.Message().Headers

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

	// TODO prozatím se ignoruje non-delivery, dokud se nevyřeší couter do funkční podoby
	_ = c.publisher.Publish(amqp.Publishing{
		ContentType: "text/plain",
		Headers:     msg.Headers, // TODO check counter and clear headers
		Body:        bodyString,
	})
}

func newCounter(publisher types.Publisher) counter {
	return counter{
		publisher: publisher,
	}
}
