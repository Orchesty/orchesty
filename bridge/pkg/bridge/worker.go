package bridge

import (
	"encoding/json"
	"errors"
	metrics "github.com/hanaboso/go-metrics/pkg"
	"github.com/hanaboso/pipes/bridge/pkg/bridge/types"
	"github.com/hanaboso/pipes/bridge/pkg/config"
	"github.com/hanaboso/pipes/bridge/pkg/enum"
	"github.com/hanaboso/pipes/bridge/pkg/model"
	"github.com/hanaboso/pipes/bridge/pkg/mongo"
	"github.com/hanaboso/pipes/bridge/pkg/rabbitmq"
	"github.com/hanaboso/pipes/bridge/pkg/utils/timex"
	"github.com/hanaboso/pipes/bridge/pkg/worker"
	"github.com/rs/zerolog"
	"github.com/rs/zerolog/log"
	"sync"
	"time"
)

type node struct {
	model.Node
	worker           types.Worker
	topologyId       string
	topologyName     string
	followers        types.Publishers
	followersList    string
	wg               *sync.WaitGroup
	cursorer         types.Publisher
	limiter          *limiter
	repeater         *repeater
	mongodb          *mongo.MongoDb
	metrics          metrics.Interface
	counter          counter
	repeaterSettings model.NodeSettingsRepeater
	limiterSettings  model.NodeSettingsLimiter
}

func (n *node) Followers() types.Publishers {
	return n.followers
}

func (n *node) Id() string {
	return n.ID
}

func (n *node) NodeName() string {
	return n.Node.Name
}

func (n *node) WorkerType() enum.WorkerType {
	return n.Node.Worker
}

func (n *node) TopologyName() string {
	return n.topologyName
}

func (n *node) Settings() model.NodeSettings {
	return n.Node.Settings
}

func (n *node) CursorPublisher() types.Publisher {
	if n.cursorer == nil {
		log.Fatal().EmbedObject(n).Msg("missing cursor publisher")
	}

	return n.cursorer
}

func (n *node) RepeaterSettings() model.NodeSettingsRepeater {
	return n.repeaterSettings
}

func (n *node) LimiterSettings() model.NodeSettingsLimiter {
	return n.limiterSettings
}

func (n *node) start() {
	log.Debug().EmbedObject(n).Msg("starting node")
	if n.Worker.ServiceType() == enum.ServiceType_Memory {
		n.wg.Done()
	} else {
		defer n.wg.Done()
	}

	for msg := range n.Messages {
		go n.process(msg)
	}
}

func (n *node) process(dto *model.ProcessMessage) {
	dto.Status = enum.MessageStatus_Consumed
	dto.SetHeader(enum.Header_NodeId, n.Node.ID)
	dto.SetHeader(enum.Header_TopologyId, n.topologyId)
	dto.SetHeader(enum.Header_WorkerFollowers, n.followersList)

	for {
		result, followers := n.innerProcess(dto)
		result.Message().Status = enum.MessageStatus_InnerProcessDone

		// OK      -> Metrics, Counter ok,  Ack
		// Stop    -> Metrics, Counter ok,  Ack
		// Error   -> Metrics, Counter err, Nack
		// Trash   -> Metrics, Counter err, Ack, Mongo
		// Pending ->     -       -         Ack

		ack := true
		status := result.Status()
		if status == enum.ProcessStatus_Error {
			ack = false
		}

		// Counter ignored in Pending status (Repeater, Limiter, UserTask)
		if status != enum.ProcessStatus_Pending {
			result.Message().Status = enum.MessageStatus_Counter
			n.counter.send(result, followers)

			result.Message().Status = enum.MessageStatus_Metrics
			n.sendMetrics(result)
		}

		// Known errors or end of repeats goes to trash and Acked
		if status == enum.ProcessStatus_Trash {
			result.Message().Status = enum.MessageStatus_Trash
			// Log message's error to show in UI what failed and went to Trash
			log.Error().
				Err(result.Error()).
				EmbedObject(result.Message()).
				Bool(enum.LogHeader_IsForUi, true).
				Send()
			if err := n.mongodb.StoreUserTask(result, n.Node.Name, n.topologyName); err != nil {
				log.Error().Err(err).EmbedObject(result.Message()).Send()
				ack = false
			}
		}

		// Ack/Nack message -> continue in case of error force another process
		result.Message().Status = enum.MessageStatus_Ack
		call := result.Message().Ack
		if !ack {
			log.Error().Err(result.Error()).EmbedObject(result.Message()).Send()
			result.Message().Status = enum.MessageStatus_Failed
			call = result.Message().Nack
		}
		if err := call(); err != nil {
			result.Message().Status = enum.MessageStatus_Fatal
			log.Error().Err(err).EmbedObject(result.Message()).Send()
			continue
		}
		result.Message().Status = enum.MessageStatus_Done

		return
	}
}

func (n *node) innerProcess(dto *model.ProcessMessage) (model.ProcessResult, int) {
	result := n.checkTrash(dto)
	if !result.IsOk() {
		return result, 0
	}

	dto.Status = enum.MessageStatus_LimiterCheck
	result = n.limiter.process(n, dto)
	if !result.IsOk() {
		return result, 0
	}

	result.Message().Status = enum.MessageStatus_BeforeProcess
	result = n.worker.BeforeProcess(n, result.Message())
	if !result.IsOk() {
		return result, 0
	}

	result.Message().Status = enum.MessageStatus_CheckResultCode
	result = n.checkResultCode(result.Message())
	if !result.IsOk() {
		return result, 0
	}

	result.Message().Status = enum.MessageStatus_AfterProcess
	result, published := n.worker.AfterProcess(n, result.Message())
	if !result.IsOk() {
		return result, 0
	}

	return result, published
}

func (n *node) checkTrash(dto *model.ProcessMessage) model.ProcessResult {
	state, err := dto.GetHeader(enum.Header_UserTaskState)
	// If header exists check for reject (accept should never come here)
	if err == nil && state == enum.UserTask_Reject {
		return dto.Stop()
	}
	// TODO - rozdělit userTask vs trash? dto.DeleteHeader(enum.Header_UserTaskState)

	return dto.Ok()
}

func (n *node) checkResultCode(dto *model.ProcessMessage) model.ProcessResult {
	switch dto.GetIntHeaderOrDefault(enum.Header_ResultCode, 0) {
	case enum.ResultCode_Repeat:
		dto.KeepRepeatHeaders = true
		return n.repeater.publish(n, dto)
	case enum.ResultCode_DoNotContinue:
		log.Info().
			Bool(enum.LogHeader_IsForUi, true).
			EmbedObject(dto).
			Msg(dto.GetHeaderOrDefault(enum.Header_ResultMessage, "do not continue"))

		return dto.Stop()
	case enum.ResultCode_LimitExceeded:
		dto.KeepRepeatHeaders = true
		return n.limiter.publish(dto)
	case enum.ResultCode_StopAndFail:
		return dto.Trash(errors.New(dto.GetHeaderOrDefault(enum.Header_ResultMessage, "stop and failed")))
	}

	return dto.Ok()
}

func (n *node) sendMetrics(dto model.ProcessResult) {
	msg := dto.Message()
	now := timex.UnixMs()

	err := n.metrics.Send(
		config.Metrics.Measurement,
		map[string]interface{}{
			"node_id": msg.GetHeaderOrDefault(enum.Header_NodeId, ""),
		},
		map[string]interface{}{
			"waiting_duration": int(msg.ProcessStarted - msg.Published),
			"worker_duration":  int(now - msg.ProcessStarted),
			"total_duration":   int(now - msg.Published),
			"result_success":   dto.IsNotError(),
			"created":          time.Now(),
		},
	)
	if err != nil {
		log.Err(err).EmbedObject(n).Send()
	}
}

func newNode(n model.Node, topologyId, topologyName string, rabbitSvc rabbitmq.RabbitMQ, wg *sync.WaitGroup, limiter *limiter, repeater *repeater, mongodb *mongo.MongoDb, metrics metrics.Interface, counter counter) *node {
	w, err := worker.Get(n.Worker)
	if err != nil {
		log.Fatal().Err(err).Send()
	}

	followers := make(map[string]types.Publisher)
	for _, follower := range n.Followers {
		// TODO this is TMP -> as it does not account for inMemory publishers
		followers[follower.Id] = rabbitSvc.GetPublisher(follower.Id)
	}

	followersString, _ := json.Marshal(n.Followers)

	node := &node{
		Node:          n,
		topologyId:    topologyId,
		topologyName:  topologyName,
		worker:        w,
		followers:     followers,
		followersList: string(followersString),
		wg:            wg,
		limiter:       limiter,
		repeater:      repeater,
		mongodb:       mongodb,
		metrics:       metrics,
		counter:       counter,
	}

	if n.Worker == enum.WorkerType_Batch {
		node.cursorer = rabbitSvc.GetPublisher(n.ID)
	}

	return node
}

// Adds node & topology id - use as .EmbedObject(n)
func (n node) MarshalZerologObject(e *zerolog.Event) {
	e.Str(enum.LogHeader_NodeId, n.ID)
	e.Str(enum.LogHeader_TopologyId, n.topologyId)
}
