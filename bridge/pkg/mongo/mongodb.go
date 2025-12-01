package mongo

import (
	"github.com/hanaboso/go-mongodb"
	"github.com/hanaboso/pipes/bridge/pkg/config"
	"github.com/hanaboso/pipes/bridge/pkg/model"
	"go.mongodb.org/mongo-driver/v2/bson"
	"go.mongodb.org/mongo-driver/v2/mongo"
	"go.mongodb.org/mongo-driver/v2/mongo/options"
)

type Field struct {
	Key  string `bson:"key" json:"key"`
	Name string `bson:"name" json:"name"`
}

type AuditEntity struct {
	ID     bson.ObjectID `bson:"_id" json:"id"`
	Key    string        `bson:"key" json:"key"`
	Name   string        `bson:"name" json:"name"`
	Fields []Field       `bson:"fields" json:"fields"`
}

func (auditEntity AuditEntity) FieldKeys() []string {
	fieldKeys := make([]string, 0, len(auditEntity.Fields))

	for _, field := range auditEntity.Fields {
		fieldKeys = append(fieldKeys, field.Key)
	}

	return fieldKeys
}

type AuditDataField struct {
	Key   string `bson:"key" json:"key"`
	Value string `bson:"value" json:"value"`
}

type AuditData struct {
	ID     bson.ObjectID    `bson:"_id" json:"id"`
	Entity string           `bson:"entity" json:"entity"`
	User   string           `bson:"user" json:"user"`
	Fields []AuditDataField `bson:"fields" json:"fields"`
}

type MongoDb struct {
	connection            *mongodb.Connection
	collection            *mongo.Collection
	auditEntityCollection *mongo.Collection
	auditDataCollection   *mongo.Collection
}

func (m *MongoDb) StoreUserTask(dto model.ProcessResult, nodeName, topologyName string) (bson.ObjectID, error) {
	document := fromDto(dto, nodeName, topologyName)
	ctx, cancel := m.connection.Context()
	inserted, err := m.collection.InsertOne(ctx, document)
	cancel()

	return inserted.InsertedID.(bson.ObjectID), err
}

func (m *MongoDb) FindAuditEntitiesByKeys(keys []string) (map[string]AuditEntity, error) {
	var auditEntity AuditEntity
	auditEntities := make(map[string]AuditEntity, len(keys))

	ctx, cancel := m.connection.Context()
	cursor, err := m.auditEntityCollection.Find(ctx, bson.M{"key": bson.M{"$in": keys}})
	defer func() {
		cancel()
		_ = cursor.Close(ctx)
	}()

	if err != nil {
		return nil, err
	}

	for cursor.Next(ctx) {
		if err = cursor.Decode(&auditEntity); err != nil {
			return nil, err
		}

		auditEntities[auditEntity.Key] = auditEntity
	}

	return auditEntities, nil
}

func (m *MongoDb) UpsertAuditData(filter, update bson.M) (*AuditData, error) {
	var auditData AuditData

	ctx, cancel := m.connection.Context()
	defer cancel()

	if _, err := m.auditDataCollection.UpdateOne(ctx, filter, update, options.UpdateOne().SetUpsert(true)); err != nil {
		return nil, err
	}

	if err := m.auditDataCollection.FindOne(ctx, filter).Decode(&auditData); err != nil {
		return nil, err
	}

	return &auditData, nil
}

func (m *MongoDb) UpdateAuditData(id string, update bson.M) error {
	ctx, cancel := m.connection.Context()
	defer cancel()

	objectId, err := bson.ObjectIDFromHex(id)

	if err != nil {
		return err
	}

	if _, err = m.auditDataCollection.UpdateOne(ctx, bson.M{"_id": objectId}, update); err != nil {
		return err
	}

	return nil
}

func (m *MongoDb) Close() {
	m.connection.Disconnect()
}

func NewMongoDb() *MongoDb {
	mongoDb := &mongodb.Connection{}
	mongoDb.Connect(config.MongoDb.Dsn)
	return &MongoDb{
		connection:            mongoDb,
		collection:            mongoDb.Database.Collection(config.MongoDb.UserTaskCollection),
		auditEntityCollection: mongoDb.Database.Collection(config.MongoDb.AuditEntityCollection),
		auditDataCollection:   mongoDb.Database.Collection(config.MongoDb.AuditDataCollection),
	}
}
