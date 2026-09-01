package bridge

import (
	"context"
	"encoding/json"
	"reflect"
	"testing"
	"time"
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

func TestNode_ProcessAudit_CaseInsensitiveUpsert(t *testing.T) {
	node, dto, connection := setupTestNode(t)
	dto.SetHeader(enum.Header_User, "Orchesty")

	auditDataId := bson.NewObjectID()
	auditEntityId := bson.NewObjectID()

	auditDataCollection := connection.Database.Collection(config.MongoDb.AuditDataCollection)
	auditEntityCollection := connection.Database.Collection(config.MongoDb.AuditEntityCollection)
	_, _ = auditEntityCollection.DeleteMany(context.Background(), bson.M{"key": "productCi"})

	auditData := mongo.AuditData{
		ID:     auditDataId,
		Entity: auditEntityId.Hex(),
		User:   "orchesty",
		Fields: []mongo.AuditDataField{
			{"sku", "S5"},
		},
	}

	auditEntity := mongo.AuditEntity{
		ID:   auditEntityId,
		Key:  "productCi",
		Name: "Product CI",
		Fields: []mongo.Field{
			{"sku", "sku"},
		},
	}

	_, _ = auditDataCollection.InsertOne(context.Background(), auditData)
	_, _ = auditEntityCollection.InsertOne(context.Background(), auditEntity)

	auditEntities := map[string]AuditEntity{
		"productCi": {
			Key: "sku",
			Fields: []map[string]string{
				{"sku": "s5"},
			},
		},
	}

	rawAuditEntities, _ := json.Marshal(auditEntities)
	dto.SetHeader(enum.Header_AuditEntityHeader, string(rawAuditEntities))

	node.processAudit(dto)

	newAuditEntitiesIds := map[string]string{}
	_ = json.Unmarshal([]byte(dto.GetHeaderOrDefault(enum.Header_AuditEntityIdsHeader, "{}")), &newAuditEntitiesIds)
	assert.Equal(t, auditDataId.Hex(), newAuditEntitiesIds["productCi:s5"])

	count, _ := auditDataCollection.CountDocuments(context.Background(), bson.M{"entity": auditEntityId.Hex()})
	assert.Equal(t, int64(1), count)

	var stored mongo.AuditData
	_ = auditDataCollection.FindOne(context.Background(), bson.M{"_id": auditDataId}).Decode(&stored)
	assert.Len(t, stored.Fields, 1)
	assert.Equal(t, "S5", stored.Fields[0].Value)
}

func TestNode_ProcessAudit_CaseInsensitiveEntityKey(t *testing.T) {
	node, dto, connection := setupTestNode(t)
	dto.SetHeader(enum.Header_User, "orchesty")

	auditEntityId := bson.NewObjectID()
	auditEntityCollection := connection.Database.Collection(config.MongoDb.AuditEntityCollection)
	_, _ = auditEntityCollection.DeleteMany(
		context.Background(),
		bson.M{"key": bson.M{"$in": []string{"ProductCased", "productcased"}}},
	)

	auditEntity := mongo.AuditEntity{
		ID:   auditEntityId,
		Key:  "ProductCased",
		Name: "Product Cased",
		Fields: []mongo.Field{
			{"id", "id"},
		},
	}

	_, _ = auditEntityCollection.InsertOne(context.Background(), auditEntity)

	auditEntities := map[string]AuditEntity{
		"productcased": {
			Key: "id",
			Fields: []map[string]string{
				{"id": "1"},
			},
		},
	}

	rawAuditEntities, _ := json.Marshal(auditEntities)
	dto.SetHeader(enum.Header_AuditEntityHeader, string(rawAuditEntities))

	node.processAudit(dto)

	newAuditEntitiesIds := map[string]string{}
	_ = json.Unmarshal([]byte(dto.GetHeaderOrDefault(enum.Header_AuditEntityIdsHeader, "{}")), &newAuditEntitiesIds)
	assert.Contains(t, newAuditEntitiesIds, "productcased:1")

	_, _ = connection.Database.Collection(config.MongoDb.AuditDataCollection).
		DeleteMany(context.Background(), bson.M{"entity": auditEntityId.Hex()})
}

func TestNode_ProcessAudit_UpdatedTimestampTouch(t *testing.T) {
	node, dto, connection := setupTestNode(t)
	dto.SetHeader(enum.Header_User, "orchesty")

	auditEntityId := bson.NewObjectID()
	auditDataCollection := connection.Database.Collection(config.MongoDb.AuditDataCollection)
	auditEntityCollection := connection.Database.Collection(config.MongoDb.AuditEntityCollection)
	_, _ = auditEntityCollection.DeleteMany(context.Background(), bson.M{"key": "productTtl"})

	auditEntity := mongo.AuditEntity{
		ID:   auditEntityId,
		Key:  "productTtl",
		Name: "Product TTL",
		Fields: []mongo.Field{
			{"id", "id"},
		},
	}

	_, _ = auditEntityCollection.InsertOne(context.Background(), auditEntity)

	auditEntities := map[string]AuditEntity{
		"productTtl": {
			Key: "id",
			Fields: []map[string]string{
				{"id": "1"},
			},
		},
	}

	rawAuditEntities, _ := json.Marshal(auditEntities)
	dto.SetHeader(enum.Header_AuditEntityHeader, string(rawAuditEntities))

	node.processAudit(dto)

	var stored bson.M
	_ = auditDataCollection.FindOne(context.Background(), bson.M{"entity": auditEntityId.Hex()}).Decode(&stored)
	firstUpdated, ok := stored["updated"].(bson.DateTime)
	assert.True(t, ok, "updated must be set on insert")

	time.Sleep(10 * time.Millisecond)

	// Second pass finds the id cached in the header, going through UpdateAuditData
	dto.SetHeader(enum.Header_AuditEntityHeader, string(rawAuditEntities))
	node.processAudit(dto)

	_ = auditDataCollection.FindOne(context.Background(), bson.M{"entity": auditEntityId.Hex()}).Decode(&stored)
	secondUpdated, ok := stored["updated"].(bson.DateTime)
	assert.True(t, ok, "updated must be set on update")
	assert.Greater(t, int64(secondUpdated), int64(firstUpdated), "updated must be refreshed on touch")

	_, _ = auditDataCollection.DeleteMany(context.Background(), bson.M{"entity": auditEntityId.Hex()})
}
