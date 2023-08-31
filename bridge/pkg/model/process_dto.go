package model

import (
	"encoding/json"
	"fmt"
	"github.com/hanaboso/pipes/bridge/pkg/enum"
	amqp "github.com/rabbitmq/amqp091-go"
	"github.com/rs/zerolog"
	"io/ioutil"
	"net/http"
	"strconv"
)

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

type MessageDto struct {
	Headers map[string]interface{} `json:"headers"`
	Body    string                 `json:"body"`
}

func (pm ProcessMessage) GetBody() []byte {
	return pm.Body
}

func (pm ProcessMessage) GetOriginalBody() string {
	if len(pm.bodyBackup) > 0 {
		return string(pm.bodyBackup)
	}

	return string(pm.Body)
}

func (pm ProcessMessage) GetHeader(header string) (string, error) {
	value, ok := pm.Headers[header]
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

func (pm ProcessMessage) GetBoolHeaderOrDefault(header string, defaultValue bool) bool {
	value, err := pm.GetHeader(header)
	if err != nil {
		return defaultValue
	}

	return value == "true" || value == "1"
}

func (pm *ProcessMessage) DeleteHeader(header string) *ProcessMessage {
	delete(pm.Headers, header)

	return pm
}

func (pm *ProcessMessage) ClearHeaders() {
	pm.DeleteHeader(enum.Header_ForceTargetQueue)
	pm.DeleteHeader(enum.Header_ResultCode)
	pm.DeleteHeader(enum.Header_ResultMessage)
	pm.DeleteHeader(enum.Header_ResultDetail)
	pm.DeleteHeader(enum.Header_UserTaskState)
	if !pm.KeepRepeatHeaders { // TODO zkontrolovat čištění a rozdělit případně zvlášť - bylo by vhod to vyhodit mimo worker a zobecnit
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

	pm.Headers[key] = value

	return pm
}

func (pm *ProcessMessage) IntoAmqp() amqp.Publishing {
	pm.ClearHeaders()
	body, _ := json.Marshal(MessageDto{
		Headers: pm.Headers,
		Body:    string(pm.Body),
	})

	return amqp.Publishing{
		ContentType: "application/json",
		Body:        body,
		Headers:     map[string]interface{}{},
	}
}

func (pm *ProcessMessage) IntoOriginalAmqp() amqp.Publishing {
	body, _ := json.Marshal(MessageDto{
		Headers: pm.Headers,
		Body:    pm.GetOriginalBody(),
	})

	return amqp.Publishing{
		ContentType: "application/json",
		Body:        body,
		Headers:     map[string]interface{}{},
	}
}

func (pm *ProcessMessage) FromHttpResponse(response *http.Response) *ProcessMessage {
	responseBody, _ := ioutil.ReadAll(response.Body)
	var messageDto MessageDto
	_ = json.Unmarshal(responseBody, &messageDto)

	pm.bodyBackup = pm.Body
	pm.Body = []byte(messageDto.Body)
	pm.KeepRepeatHeaders = false
	pm.Headers = messageDto.Headers
	if pm.Headers == nil {
		pm.Headers = make(map[string]interface{})
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

func (pm *ProcessMessage) CopyBatchItem(item MessageDto) *ProcessMessage {
	copied := make(map[string]interface{}, len(pm.Headers))
	for i, j := range pm.Headers {
		copied[i] = j
	}
	for key, value := range item.Headers {
		copied[key] = value
	}

	return &ProcessMessage{
		Body:    []byte(item.Body),
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

// UserTask / Limiter / Repeater - counter doesn't receive message
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
