package service

import (
	"errors"
	"github.com/hanaboso/pipes/bridge/pkg/bridge"
	"github.com/hanaboso/pipes/bridge/pkg/model"
	"github.com/hanaboso/pipes/bridge/pkg/service/worker"
	"github.com/hanaboso/pipes/bridge/pkg/topology"
)

type Processor struct {
	worker worker.Container
	// Map nodeId -> rabbitMq/inMemory publisher
	publishers map[string]interface{}
}

// TODO This is all just an example for now

func (processor Processor) handleFunction() {
	// Built with topologyBuilder
	nodes := topology.Nodes{}
	_ = nodes

	// Each node will have input Channel
	_ = processor.publishers
	// should be created for InMemory messaging, otherwise rabbitMq publishers are taken

	// RabbitMq consumer for each Node
	consumers := []interface{}{}
	_ = consumers

	go func() {
		// Run each node input channel in async GoRoutine
		// Closing each input channel on gracefulShutdown
		for _, node := range nodes {
			go func() {
				defer bridge.AwaitClose()()
				// Close input channel of given node to stop processing ... defer there is not enough
				// as messages are processed in different go routines -> thus they need their own notification
			}()

			for message := range node.Messages {
				go processor.outerProcess(message)
			}
		}
	}()
}

func (processor Processor) outerProcess(dto *model.ProcessDto) {
	defer bridge.Processing()() // marks message as in-process resulting in waiting for shutdown

	result := processor.innerProcess(dto)
	if result.IsError() {
		// NACK
		return
	}

	// ACK
}

func (processor Processor) innerProcess(dto *model.ProcessDto) model.ProcessResult {
	result := processor.worker.BeforeProcess(dto)
	if !result.IsOk() {
		return result
	}

	result = limiter(result.Message)
	if !result.IsOk() {
		return result
	}

	result = repeater(result.Message)
	if !result.IsOk() {
		return result
	}

	result = processor.worker.AfterProcess(result.Message)
	if !result.IsOk() {
		return result
	}

	result = processor.broadcast(result.Message)
	if !result.IsOk() {
		return result
	}

	return OkResult(result.Message)
}

func (processor Processor) broadcast(dto *model.ProcessDto) model.ProcessResult {
	// Iterate each allowed follower (taken routing in account)
	for _, followerId := range []string{} {
		publisher := processor.publishers[followerId]
		// Publish message... publisher is either RabbitMq publisher OR memory channel directing right into next process
		_ = publisher
	}

	return OkResult(dto)
}

func limiter(dto *model.ProcessDto) model.ProcessResult {
	// Error -> Nack
	return ErrorResult(dto, errors.New("něco se stalo"))
}

func repeater(dto *model.ProcessDto) model.ProcessResult {
	// Repeater to odeslal do fronty -> zastav další zpracování + Ack
	return StopResult(dto)
}
