package bridge

import (
	"context"
	"encoding/json"
	"reflect"
	"testing"
	"unsafe"

	"github.com/hanaboso/go-mongodb"
	"github.com/hanaboso/pipes/bridge/pkg/config"
	"github.com/hanaboso/pipes/bridge/pkg/enum"
	"github.com/hanaboso/pipes/bridge/pkg/model"
	"github.com/hanaboso/pipes/bridge/pkg/mongo"
	"github.com/stretchr/testify/assert"
	"go.mongodb.org/mongo-driver/v2/bson"
)

func setupTestNode(t *testing.T) (*node, *model.ProcessMessage, *mongodb.Connection) {
	mongoDb := mongo.NewMongoDb()

	return &node{mongodb: mongoDb}, &model.ProcessMessage{}, (*mongodb.Connection)(unsafe.Pointer(reflect.ValueOf(mongoDb).Elem().FieldByName("connection").Pointer()))
}

func TestNode_ProcessAudit_AuditEntityHeader(t *testing.T) {
	node, dto, connection := setupTestNode(t)
	dto.SetHeader(enum.Header_User, "orchesty")

	auditDataId := bson.NewObjectID()
	auditEntityIdOne := bson.NewObjectID()
	auditEntityIdTwo := bson.NewObjectID()

	auditData := mongo.AuditData{
		ID:     auditDataId,
		Entity: auditEntityIdOne.Hex(),
		User:   "orchesty",
		Fields: []mongo.AuditDataField{
			{"id", "1"},
			{"externalId", "A"},
		},
	}

	auditEntityOne := mongo.AuditEntity{
		ID:   auditEntityIdOne,
		Key:  "postOne",
		Name: "Post One",
		Fields: []mongo.Field{
			{"id", "id"},
			{"externalId", "externalId"},
		},
	}

	auditEntityTwo := mongo.AuditEntity{
		ID:   auditEntityIdTwo,
		Key:  "postTwo",
		Name: "Post Two",
		Fields: []mongo.Field{
			{"id", "id"},
			{"externalId", "externalId"},
		},
	}

	_, _ = connection.Database.Collection(config.MongoDb.AuditDataCollection).InsertOne(context.Background(), auditData)
	_, _ = connection.Database.Collection(config.MongoDb.AuditEntityCollection).InsertMany(context.Background(), []mongo.AuditEntity{auditEntityOne, auditEntityTwo})

	auditEntities := map[string]AuditEntity{
		"postOne": {
			Key: "id",
			Fields: []map[string]string{
				{"id": "1", "externalId": "A"},
				{"id": "2", "externalId": "B"},
			},
		},
		"postTwo": {
			Key: "id",
			Fields: []map[string]string{
				{"id": "1", "externalId": "A"},
				{"id": "1", "externalId": "A", "unknown": ""},
				{"externalId": "A"},
			},
		},
		"unknown": {
			Key: "id",
			Fields: []map[string]string{
				{"id": "1", "externalId": "A"},
			},
		},
	}

	rawAuditEntities, _ := json.Marshal(auditEntities)
	dto.SetHeader(enum.Header_AuditEntityHeader, string(rawAuditEntities))

	auditEntitiesFields := map[string]AuditEntityFields{
		"postOne": {auditEntityIdOne.Hex(), []string{"id", "externalId"}},
	}

	rawAuditEntitiesFields, _ := json.Marshal(auditEntitiesFields)
	dto.SetHeader(enum.Header_AuditEntityFieldsHeader, string(rawAuditEntitiesFields))

	auditEntitiesIds := map[string]string{
		"postOne:1": auditDataId.Hex(),
	}

	rawAuditEntitiesIds, _ := json.Marshal(auditEntitiesIds)
	dto.SetHeader(enum.Header_AuditEntityIdsHeader, string(rawAuditEntitiesIds))

	node.processAudit(dto)

	_, err := dto.GetHeader(enum.Header_AuditEntityHeader)
	assert.Error(t, err)

	newAuditEntitiesIds := map[string]string{}
	_ = json.Unmarshal([]byte(dto.GetHeaderOrDefault(enum.Header_AuditEntityIdsHeader, "{}")), &newAuditEntitiesIds)

	auditEntitiesIdsKeys := []string{}

	for k := range newAuditEntitiesIds {
		auditEntitiesIdsKeys = append(auditEntitiesIdsKeys, k)
	}

	assert.ElementsMatch(t, []string{"postOne:1", "postOne:2", "postTwo:1"}, auditEntitiesIdsKeys)
}

func TestNode_ProcessAudit_NoAuditEntityHeader(t *testing.T) {
	node, dto, _ := setupTestNode(t)
	node.processAudit(dto)

	assert.Empty(t, dto.GetHeaderOrDefault(enum.Header_AuditEntityIdsHeader, ""))
}
