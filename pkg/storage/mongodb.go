package storage

import (
	"cron/pkg/utils"
	"net/http"

	"cron/pkg/config"
	"github.com/hanaboso/go-mongodb"
	"go.mongodb.org/mongo-driver/bson/primitive"
	"go.mongodb.org/mongo-driver/mongo"
	"go.mongodb.org/mongo-driver/mongo/options"

	cronParser "github.com/robfig/cron/v3"
)

// Interface represents abstract storage implementation
type Interface interface {
	Connect()
	Disconnect()
	IsConnected() bool
	GetAll() ([]Cron, error)
	Create(*Cron) (*mongo.InsertOneResult, error)
	Update(*Cron) (*mongo.UpdateResult, error)
	Upsert(*Cron) (*mongo.UpdateResult, error)
	Delete(*Cron) (*mongo.DeleteResult, error)
	BatchCreate([]Cron) (*mongo.InsertManyResult, error)
	BatchUpdate([]Cron) (*mongo.UpdateResult, error)
	BatchUpsert([]Cron) (*mongo.UpdateResult, error)
	BatchDelete([]Cron) (*mongo.DeleteResult, error)
}

// MongoDBImplementation represents MongoDB storage implementation
type MongoDBImplementation struct {
	connection *mongodb.Connection
}

// MongoDB represents MongoDB storage implementation
var MongoDB Interface = &MongoDBImplementation{}

// Connect connects to MongoDB
func (m *MongoDBImplementation) Connect() {
	config.Config.Logger.Infof("Connecting to MongoDB: %s", config.Config.MongoDB.Dsn)

	m.connection = &mongodb.Connection{}
	m.connection.Connect(config.Config.MongoDB.Dsn)

	config.Config.Logger.Info("MongoDB successfully connected!")
}

// Disconnect disconnects from MongoDB
func (m *MongoDBImplementation) Disconnect() {
	m.connection.Disconnect()
}

// IsConnected checks MongoDB connection status
func (m *MongoDBImplementation) IsConnected() bool {
	return m.connection.IsConnected()
}

// GetAll gets all crons
func (m *MongoDBImplementation) GetAll() ([]Cron, error) {
	context, cancel := m.connection.Context()
	defer cancel()
	var cron Cron
	var crons []Cron

	cursor, err := m.connection.Database.Collection(config.Config.MongoDB.Collection).Find(context, map[string]interface{}{})

	if err != nil {
		logMongoDBError(err)

		return crons, err
	}

	defer func() {
		if err := cursor.Close(nil); err != nil {
			logMongoDBError(err)
		}
	}()

	for cursor.Next(nil) {
		err = cursor.Decode(&cron)

		if err != nil {
			logMongoDBError(err)

			return crons, err
		}

		crons = append(crons, cron)
	}

	return crons, nil
}

// Create creates cron
func (m *MongoDBImplementation) Create(cron *Cron) (*mongo.InsertOneResult, error) {
	if err := m.validate(cron); err != nil {
		return nil, err
	}

	context, cancel := m.connection.Context()
	defer cancel()

	cron.ID = primitive.NewObjectID()
	result, err := m.connection.Database.Collection(config.Config.MongoDB.Collection).InsertOne(context, cron)

	if err != nil {
		logMongoDBError(err)
	}

	return result, err
}

// Update updates cron
func (m *MongoDBImplementation) Update(cron *Cron) (*mongo.UpdateResult, error) {
	result, err := m.createUpdate(cron, false)

	if err != nil {
		return result, err
	}

	if result.MatchedCount == 0 {
		return nil, &utils.Error{
			Code:    http.StatusNotFound,
			Message: "Unknown CRON!",
		}
	}

	return result, err
}

// Upsert upserts cron
func (m *MongoDBImplementation) Upsert(cron *Cron) (*mongo.UpdateResult, error) {
	return m.createUpdate(cron, true)
}

// Delete deletes cron
func (m *MongoDBImplementation) Delete(cron *Cron) (*mongo.DeleteResult, error) {
	context, cancel := m.connection.Context()
	defer cancel()

	result, err := m.connection.Database.Collection(config.Config.MongoDB.Collection).DeleteOne(context, map[string]interface{}{
		topology: cron.Topology,
		node:     cron.Node,
	})

	if err != nil {
		logMongoDBError(err)
	}

	return result, err
}

// BatchCreate creates crons
func (m *MongoDBImplementation) BatchCreate(crons []Cron) (*mongo.InsertManyResult, error) {
	context, cancel := m.connection.Context()
	defer cancel()

	var innerCrons []interface{}

	for _, cron := range crons {
		if err := m.validate(&cron); err != nil {
			return nil, err
		}

		cron.ID = primitive.NewObjectID()
		innerCrons = append(innerCrons, cron)
	}

	result, err := m.connection.Database.Collection(config.Config.MongoDB.Collection).InsertMany(context, innerCrons)

	if err != nil {
		logMongoDBError(err)
	}

	return result, err
}

// BatchUpdate updates crons
func (m *MongoDBImplementation) BatchUpdate(crons []Cron) (*mongo.UpdateResult, error) {
	updateResult := &mongo.UpdateResult{
		MatchedCount:  0,
		ModifiedCount: 0,
		UpsertedCount: 0,
		UpsertedID:    nil,
	}

	for _, cron := range crons {
		result, err := m.Update(&cron)

		if err != nil {
			return updateResult, err
		}

		updateResult.MatchedCount += result.MatchedCount
		updateResult.ModifiedCount += result.ModifiedCount
		updateResult.UpsertedCount += result.UpsertedCount
	}

	return updateResult, nil
}

// BatchUpsert upserts crons
func (m *MongoDBImplementation) BatchUpsert(crons []Cron) (*mongo.UpdateResult, error) {
	updateResult := &mongo.UpdateResult{
		MatchedCount:  0,
		ModifiedCount: 0,
		UpsertedCount: 0,
		UpsertedID:    nil,
	}

	for _, cron := range crons {
		result, err := m.Upsert(&cron)

		if err != nil {
			return updateResult, err
		}

		updateResult.MatchedCount += result.MatchedCount
		updateResult.ModifiedCount += result.ModifiedCount
		updateResult.UpsertedCount += result.UpsertedCount
	}

	return updateResult, nil
}

// BatchDelete deletes crons
func (m *MongoDBImplementation) BatchDelete(crons []Cron) (*mongo.DeleteResult, error) {
	context, cancel := m.connection.Context()
	defer cancel()

	result, err := m.connection.Database.Collection(config.Config.MongoDB.Collection).DeleteMany(context, m.createDelete(crons))

	if err != nil {
		logMongoDBError(err)
	}

	return result, err
}

func createSet(data map[string]interface{}) map[string]interface{} {
	return map[string]interface{}{"$set": data}
}

func (m *MongoDBImplementation) createUpdate(cron *Cron, upsert bool) (*mongo.UpdateResult, error) {
	if err := m.validate(cron); err != nil {
		return nil, err
	}

	context, cancel := m.connection.Context()
	defer cancel()

	result, err := m.connection.Database.Collection(config.Config.MongoDB.Collection).UpdateOne(context, map[string]interface{}{
		topology: cron.Topology,
		node:     cron.Node,
	}, createSet(map[string]interface{}{
		time:    cron.Time,
		command: cron.Command,
	}), &options.UpdateOptions{Upsert: &upsert})

	if err != nil {
		logMongoDBError(err)
	}

	return result, err
}

func (m *MongoDBImplementation) createDelete(crons []Cron) interface{} {
	var ors []interface{}

	for _, cron := range crons {
		ors = append(ors, map[string]interface{}{"$and": []map[string]interface{}{{
			topology: cron.Topology,
			node:     cron.Node,
		}}})
	}

	return map[string]interface{}{"$or": ors}
}

func (*MongoDBImplementation) validate(cron *Cron) *utils.Error {
	if _, err := cronParser.ParseStandard(cron.Time); err != nil {
		logCronError(err)

		return &utils.Error{
			Code:    http.StatusBadRequest,
			Message: "Unknown CRON expression!",
		}
	}

	return nil
}

func logMongoDBError(error error) {
	config.Config.Logger.Errorf("Unexpected MongoDB error: %s", error.Error())
}

func logCronError(error error) {
	config.Config.Logger.Errorf("Unexpected CRON error: %s", error.Error())
}
