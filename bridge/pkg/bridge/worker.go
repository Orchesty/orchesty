package bridge

import (
	"encoding/json"
	"errors"
	"sort"
	"strings"
	"sync"
	"time"

	metrics "github.com/hanaboso/go-metrics/pkg"
	"github.com/hanaboso/go-rabbitmq/pkg/rabbitmq"
	"github.com/hanaboso/pipes/bridge/pkg/bridge/types"
	"github.com/hanaboso/pipes/bridge/pkg/config"
	"github.com/hanaboso/pipes/bridge/pkg/enum"
	"github.com/hanaboso/pipes/bridge/pkg/model"
	"github.com/hanaboso/pipes/bridge/pkg/mongo"
	"github.com/hanaboso/pipes/bridge/pkg/rabbit"
	"github.com/hanaboso/pipes/bridge/pkg/topology"
	"github.com/hanaboso/pipes/bridge/pkg/utils/arrayx"
	"github.com/hanaboso/pipes/bridge/pkg/utils/timex"
	"github.com/hanaboso/pipes/bridge/pkg/worker"
	"github.com/rs/zerolog"
	"github.com/rs/zerolog/log"
	"go.mongodb.org/mongo-driver/v2/bson"
)

type AuditEntity struct {
	Key    string              `json:"key"`
	Fields []map[string]string `json:"fields"`
}

type AuditEntityFields struct {
	Entity string   `json:"entity"`
	Fields []string `json:"fields"`
}

type node struct {
	model.Node
	worker        types.Worker
	consumer      *rabbitmq.Consumer
	topologyId    string
	topologyName  string
	followers     types.Publishers
	followersList string
	wg            *sync.WaitGroup
	cursorer      types.Publisher
	limiter       limiter
	repeater      repeater
	mongodb       *mongo.MongoDb
	metrics       metrics.Interface
	counter       counter
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

func (n *node) Application() string {
	return n.Node.Application
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

func (n *node) start() {
	log.Debug().EmbedObject(n).Msgf("starting node: %s", n.NodeName())
	if n.Worker.ServiceType() == enum.ServiceType_Memory {
		n.wg.Done()
	} else {
		defer n.wg.Done()
	}

	wg := &sync.WaitGroup{}
	for msg := range n.consumer.Consume(false) {
		wg.Add(1)
		go n.process(rabbit.ParseMessage(msg, wg))
	}

	wg.Wait() // Await for (n)acking of messages that have been sent to process before closing n.wg.Done() above
}

func (n *node) process(dto *model.ProcessMessage) bool {
	dto.Status = enum.MessageStatus_Consumed
	dto.SetHeader(enum.Header_NodeId, n.Node.ID)
	dto.SetHeader(enum.Header_NodeName, n.Node.Name)
	dto.SetHeader(enum.Header_TopologyId, n.topologyId)
	dto.SetHeader(enum.Header_WorkerFollowers, n.followersList)
	if topology.IsSystemTopology(n.topologyName) {
		dto.SetHeader(enum.Header_SystemEvent, "true")
	}

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
		if status != enum.ProcessStatus_Pending && status != enum.ProcessStatus_Error {
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
			if trashId, err := n.mongodb.StoreUserTask(result, n.Node.Name, n.topologyName); err != nil {
				log.Error().Err(err).EmbedObject(result.Message()).Send()
				ack = false
			} else {
				trashId := trashId.Hex()
				sendFinishedProcess(result.Message(), enum.StatusType_TrashMessage, &trashId, n.topologyName)
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

		return ack
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

	n.processAudit(result.Message())

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
			"node_id":        msg.GetHeaderOrDefault(enum.Header_NodeId, ""),
			"topology_id":    msg.GetHeaderOrDefault(enum.Header_TopologyId, ""),
			"correlation_id": msg.GetHeaderOrDefault(enum.Header_CorrelationId, ""),
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

func newNode(n model.Node, topologyId, topologyName string, rabbitContainer rabbit.Container, wg *sync.WaitGroup, limiter limiter, repeater repeater, mongodb *mongo.MongoDb, metrics metrics.Interface, counter counter) *node {
	w, err := worker.Get(n.Worker)
	if err != nil {
		log.Fatal().Err(err).Send()
	}

	followers := make(types.Publishers)
	for _, follower := range n.Followers {
		// TODO this is TMP -> as it does not account for inMemory publishers
		followers[follower.Id] = rabbitContainer.Publishers[follower.Id]
	}

	followersString, _ := json.Marshal(n.Followers)

	node := &node{
		Node:          n,
		topologyId:    topologyId,
		topologyName:  topologyName,
		worker:        w,
		consumer:      rabbitContainer.Consumers[n.ID],
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
		node.cursorer = rabbitContainer.Publishers[n.ID]
	}

	return node
}

// Adds node & topology id - use as .EmbedObject(n)
func (n node) MarshalZerologObject(e *zerolog.Event) {
	e.Str(enum.LogHeader_NodeId, n.ID)
	e.Str(enum.LogHeader_TopologyId, n.topologyId)
}

func (n *node) processAudit(dto *model.ProcessMessage) {
	rawAuditEntities, err := dto.GetHeader(enum.Header_AuditEntityHeader)

	if err != nil {
		return
	}

	auditEntities := make(map[string]AuditEntity)

	if err = json.Unmarshal([]byte(rawAuditEntities), &auditEntities); err != nil {
		log.Err(err).EmbedObject(dto).Send()

		return
	}

	auditEntitiesFields := n.processAuditEntitiesFields(dto, auditEntities)
	n.processAuditEntities(dto, auditEntities, auditEntitiesFields)
	n.processAuditEntitiesIds(dto, auditEntities, auditEntitiesFields)

	dto.DeleteHeader(enum.Header_AuditEntityHeader)
}

func (n *node) processAuditEntities(dto *model.ProcessMessage, auditEntities map[string]AuditEntity, auditEntitiesFields map[string]AuditEntityFields) {
	for auditEntityKey, auditEntity := range auditEntities {
		if _, has := auditEntitiesFields[auditEntityKey]; !has {
			delete(auditEntities, auditEntityKey)

			keys := make([]string, 0, len(auditEntitiesFields))

			for key := range auditEntitiesFields {
				keys = append(keys, key)
			}

			sort.Strings(keys)

			log.
				Warn().
				EmbedObject(dto).
				Msgf("Unknown audit entity '%s' (use '%s')", auditEntityKey, strings.Join(keys, "', '"))

			continue
		}

		auditEntityFieldsForDeletion := make(map[int]bool, len(auditEntity.Fields))

		for i, fields := range auditEntity.Fields {
			for auditEntityFieldKey := range fields {
				if !arrayx.InArray(auditEntitiesFields[auditEntityKey].Fields, auditEntityFieldKey) {
					auditEntityFieldsForDeletion[i] = true
					keys := auditEntitiesFields[auditEntityKey].Fields
					sort.Strings(keys)

					log.
						Warn().
						EmbedObject(dto).
						Msgf("Unknown audit entity field '%s.%s' (use '%s')", auditEntityKey, auditEntityFieldKey, strings.Join(keys, "', '"))

					break
				}

				if _, has := fields[auditEntity.Key]; !has {
					auditEntityFieldsForDeletion[i] = true

					log.
						Warn().
						EmbedObject(dto).
						Msgf("Missing audit entity field '%s.%s'", auditEntityKey, auditEntity.Key)

					break
				}
			}
		}

		newFields := make([]map[string]string, 0, len(auditEntity.Fields))

		for i, field := range auditEntity.Fields {
			if has := auditEntityFieldsForDeletion[i]; !has {
				newFields = append(newFields, field)
			}
		}

		auditEntity.Fields = newFields
		auditEntities[auditEntityKey] = auditEntity
	}
}

func (n *node) processAuditEntitiesIds(dto *model.ProcessMessage, auditEntities map[string]AuditEntity, auditEntitiesFields map[string]AuditEntityFields) {
	user := dto.GetHeaderOrDefault(enum.Header_User, "")
	auditEntitiesIds := make(map[string]string)
	rawAuditEntitiesIds := dto.GetHeaderOrDefault(enum.Header_AuditEntityIdsHeader, "{}")

	if err := json.Unmarshal([]byte(rawAuditEntitiesIds), &auditEntitiesIds); err != nil {
		log.Err(err).EmbedObject(dto).Send()
	}

	for auditEntityKey, auditEntity := range auditEntities {
		for _, field := range auditEntity.Fields {
			entityKey := auditEntityKey + ":" + field[auditEntity.Key]

			if _, has := auditEntitiesIds[entityKey]; !has {
				dbAuditEntity, err := n.mongodb.UpsertAuditData(
					bson.M{
						"user":   user,
						"entity": auditEntitiesFields[auditEntityKey].Entity,
						"fields": bson.M{
							"$elemMatch": bson.M{
								"key":   auditEntity.Key,
								"value": field[auditEntity.Key],
							},
						},
					},
					generateAddToSet(field),
				)

				if err != nil {
					log.Err(err).EmbedObject(dto).Send()

					continue
				}

				auditEntitiesIds[entityKey] = dbAuditEntity.ID.Hex()

				continue
			}

			err := n.mongodb.UpdateAuditData(auditEntitiesIds[entityKey], generateAddToSet(field))

			if err != nil {
				log.Err(err).EmbedObject(dto).Send()
			}
		}
	}

	if newRawAuditEntitiesIds, err := json.Marshal(auditEntitiesIds); err != nil {
		log.Err(err).EmbedObject(dto).Send()
	} else {
		dto.SetHeader(enum.Header_AuditEntityIdsHeader, string(newRawAuditEntitiesIds))
	}

}

func (n *node) processAuditEntitiesFields(dto *model.ProcessMessage, auditEntities map[string]AuditEntity) map[string]AuditEntityFields {
	auditEntitiesFields := make(map[string]AuditEntityFields)
	rawAuditEntitiesFields := dto.GetHeaderOrDefault(enum.Header_AuditEntityFieldsHeader, "{}")

	if err := json.Unmarshal([]byte(rawAuditEntitiesFields), &auditEntitiesFields); err != nil {
		log.Err(err).EmbedObject(dto).Send()
	}

	keys := make([]string, 0)

	for k := range auditEntities {
		if _, has := auditEntitiesFields[k]; !has {
			keys = append(keys, k)
		}
	}

	if auditEntities, err := n.mongodb.FindAuditEntitiesByKeys(keys); err == nil {
		for k, auditEntity := range auditEntities {
			auditEntitiesFields[k] = AuditEntityFields{
				Entity: auditEntity.ID.Hex(),
				Fields: auditEntity.FieldKeys(),
			}
		}
	}

	if newRawAuditEntitiesFields, err := json.Marshal(auditEntitiesFields); err != nil {
		log.Err(err).EmbedObject(dto).Send()
	} else {
		dto.SetHeader(enum.Header_AuditEntityFieldsHeader, string(newRawAuditEntitiesFields))
	}

	return auditEntitiesFields
}

func generateAddToSet(auditEntityFields map[string]string) bson.M {
	fields := make([]mongo.AuditDataField, 0, len(auditEntityFields))

	for key, value := range auditEntityFields {
		fields = append(fields, mongo.AuditDataField{
			Key:   key,
			Value: value,
		})
	}

	return bson.M{
		"$addToSet": bson.M{
			"fields": bson.M{
				"$each": fields,
			},
		},
	}
}
