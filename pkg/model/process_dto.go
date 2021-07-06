package model

import (
	"fmt"
	"github.com/hanaboso/pipes/bridge/pkg/enum"
	"github.com/rs/zerolog"
	"github.com/streadway/amqp"
	"io/ioutil"
	"net/http"
	"strconv"
	"strings"
)

const HeaderPrefix = "pf-"

type ProcessMessage struct {
	Body       []byte
	bodyBackup []byte // Used for cases like repeat or cursor when original unchanged body is required
	Headers    map[string]interface{}
	Exchange   string
	RoutingKey string
	Ack        func() error
	Nack       func() error
	// Helpers
	KeepRepeatHeaders bool
	// Metrics informations
	Published      int64
	ProcessStarted int64
	Status         enum.MessageStatus
}

func (pm ProcessMessage) GetBody() []byte {
	return pm.Body
}

func (pm ProcessMessage) GetOriginalBody() []byte {
	if len(pm.bodyBackup) > 0 {
		return pm.bodyBackup
	}

	return pm.Body
}

func (pm ProcessMessage) GetHeader(header string) (string, error) {
	value, ok := pm.Headers[Prefix(header)]
	if !ok {
		return "", fmt.Errorf("requested header [%s] does not exist", header)
	}

	return fmt.Sprint(value), nil
}

func (pm ProcessMessage) GetHeaderOrDefault(header, defaultValue string) string {
	value, err := pm.GetHeader(header)
	if err != nil {
		return defaultValue
	}

	return value
}

func (pm ProcessMessage) GetIntHeader(header string) (int, error) {
	value, err := pm.GetHeader(header)
	if err != nil {
		return 0, err
	}

	ivalue, err := strconv.Atoi(value)
	if err != nil {
		return 0, fmt.Errorf("header [%s] of value [%s] was expected to be an integer", header, value)
	}

	return ivalue, nil
}

func (pm ProcessMessage) GetIntHeaderOrDefault(header string, defaultValue int) int {
	value, err := pm.GetIntHeader(header)
	if err != nil {
		return defaultValue
	}

	return value
}

func (pm *ProcessMessage) DeleteHeader(header string) *ProcessMessage {
	delete(pm.Headers, Prefix(header))

	return pm
}

func (pm *ProcessMessage) ClearHeaders() {
	pm.DeleteHeader(enum.Header_ForceTargetQueue)
	pm.DeleteHeader(enum.Header_ResultCode)
	pm.DeleteHeader(enum.Header_ResultMessage)
	if !pm.KeepRepeatHeaders { // TODO zkontrolovat čištění a rozdělit případně zvlášť
		pm.DeleteHeader(enum.Header_RepeatHops)
		pm.DeleteHeader(enum.Header_RepeatInterval)
		pm.DeleteHeader(enum.Header_RepeatQueue)
		pm.DeleteHeader(enum.Header_RepeatMaxHops)
		pm.DeleteHeader(enum.Header_LimitReturnExchange)
		pm.DeleteHeader(enum.Header_LimitMessageFromLimiter)
		pm.DeleteHeader(enum.Header_LimitReturnRoutingKey)
	}
}

func (pm *ProcessMessage) SetHeader(key, value string) *ProcessMessage {
	if pm.Headers == nil {
		pm.Headers = make(map[string]interface{})
	}

	pm.Headers[Prefix(key)] = value

	return pm
}

func (pm *ProcessMessage) IntoAmqp() amqp.Publishing {
	pm.ClearHeaders()

	return amqp.Publishing{
		ContentType: "text/plain",
		Headers:     pm.Headers,
		Body:        pm.Body,
	}
}

func (pm *ProcessMessage) IntoOriginalAmqp() amqp.Publishing {
	return amqp.Publishing{
		ContentType: "text/plain",
		Headers:     pm.Headers,
		Body:        pm.GetOriginalBody(),
	}
}

func (pm *ProcessMessage) FromHttpResponse(response *http.Response) *ProcessMessage {
	responseBody, _ := ioutil.ReadAll(response.Body)
	pm.bodyBackup = pm.Body
	pm.Body = responseBody
	pm.KeepRepeatHeaders = false
	pm.Headers = make(map[string]interface{})
	for key, values := range response.Header {
		if len(values) > 0 {
			key = strings.ToLower(key)
			if strings.HasPrefix(key, HeaderPrefix) {
				pm.Headers[key] = values[0]
			}
		}
	}

	return pm
}

func (pm *ProcessMessage) Copy() *ProcessMessage {
	copied := make(map[string]interface{}, len(pm.Headers))
	for i, j := range pm.Headers {
		copied[i] = j
	}

	return &ProcessMessage{
		Body:    pm.Body,
		Headers: copied,
		Ack:     func() error { return nil },
		Nack:    func() error { return nil },
	}
}

func (pm *ProcessMessage) CopyWithBody(body []byte) *ProcessMessage {
	copied := make(map[string]interface{}, len(pm.Headers))
	for i, j := range pm.Headers {
		copied[i] = j
	}

	return &ProcessMessage{
		Body:    body,
		Headers: copied,
		Ack:     func() error { return nil },
		Nack:    func() error { return nil },
	}
}

func (pm *ProcessMessage) Ok() ProcessResult {
	return OkResult(pm)
}

// StopAndOk - stop process and sends Ok with no followers to counter
func (pm *ProcessMessage) Stop() ProcessResult {
	return StopResult(pm)
}

// UserTask - counter doesn't receive message
func (pm *ProcessMessage) Pending() ProcessResult {
	return PendingResult(pm)
}

// Forces Nack and redelivery -> does not count repeater, counter, ...
func (pm *ProcessMessage) Error(err error) ProcessResult {
	return ErrorResult(pm, err)
}

func (pm *ProcessMessage) Trash(err error) ProcessResult {
	return TrashResult(pm, err)
}

// Adds node data -> best to use as .EmbedObject(dto)
func (pm ProcessMessage) MarshalZerologObject(e *zerolog.Event) {
	e.Str(enum.LogHeader_CorrelationId, pm.GetHeaderOrDefault(enum.Header_CorrelationId, ""))
	e.Str(enum.LogHeader_ProcessId, pm.GetHeaderOrDefault(enum.Header_ProcessId, ""))
	e.Str(enum.LogHeader_TopologyId, pm.GetHeaderOrDefault(enum.Header_TopologyId, ""))
	e.Str(enum.LogHeader_NodeId, pm.GetHeaderOrDefault(enum.Header_NodeId, ""))
}

func Prefix(header string) string {
	return fmt.Sprintf("%s%s", HeaderPrefix, header)
}
