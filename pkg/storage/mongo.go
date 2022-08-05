package storage

import (
	"fmt"
	"github.com/hanaboso/go-mongodb"
	log "github.com/sirupsen/logrus"
	"go.mongodb.org/mongo-driver/bson/primitive"
	"go.mongodb.org/mongo-driver/mongo"
	"go.mongodb.org/mongo-driver/mongo/options"
	"limiter/pkg/config"
	"limiter/pkg/logger"
)

// MongoDefault represents the mongo db connection
type MongoDefault struct {
	connection *mongodb.Connection
	collection string
	session    mongo.Session
	logger     logger.Logger
}

// Connect connects to database
func (m *MongoDefault) Connect() {
	log.Info("Connecting to MongoDB")

	m.connection = &mongodb.Connection{}
	m.connection.Connect(config.Config.MongoDB.Dsn)
	var err error
	m.session, err = m.connection.StartSession()
	if err != nil {
		log.Error(err)
	}
	log.Info("MongoDB successfully connected!")
}

// Disconnect disconnects from database
func (m *MongoDefault) Disconnect() {
	m.connection.Disconnect()
}

// IsConnected checks connection status
func (m *MongoDefault) IsConnected() bool {
	return m.connection.IsConnected()
}

// CanHandle just calls Exists method
func (m *MongoDefault) CanHandle(key string, _ int, _ int, _ string, _ int, _ int) (bool, error) {
	return m.Exists(key)
}

// Remove removes the document by it's unique id
func (m *MongoDefault) Remove(_ string, id primitive.ObjectID) (bool, error) {
	innerContext, cancel := m.connection.Context()
	defer cancel()

	if _, err := m.connection.Database.Collection(m.collection).DeleteOne(innerContext, primitive.M{
		"_id": id,
	}); err != nil {
		return false, err
	}

	return true, nil
}

// ClearCacheItem remove key from memory cache
func (m *MongoDefault) ClearCacheItem(_ string, _ int) bool {
	return true
}

// Save persists Message to mongo storage and returns it's limitKey
func (m *MongoDefault) Save(message *Message) (string, error) {
	innerContext, cancel := m.connection.Context()
	defer cancel()

	if _, err := m.connection.Database.Collection(m.collection).InsertOne(innerContext, message); err != nil {
		return message.LimitKey, err
	}

	return message.LimitKey, nil
}

// CreateIndex - RunCommand run command
func (m *MongoDefault) CreateIndex(index mongo.IndexModel) error {
	innerContext, cancel := m.connection.Context()
	defer cancel()

	if _, err := m.connection.Database.Collection(m.collection).Indexes().CreateOne(innerContext, index); err != nil {
		return err
	}

	return nil
}

// Exists return boolean if any document found with given key or returns error if some mongo error occurs
func (m *MongoDefault) Exists(key string) (bool, error) {
	innerContext, cancel := m.connection.Context()
	defer cancel()

	limit64 := int64(1)

	var messages []*Message
	cursor, err := m.connection.Database.Collection(m.collection).Find(innerContext, primitive.M{"limitkey": key},
		&options.FindOptions{
			Limit: &limit64,
			Sort: primitive.D{
				{"created", 1},
			},
		})

	if err != nil {
		return false, err
	}

	err = cursor.All(innerContext, &messages)
	if err != nil {
		return false, err
	}

	return len(messages) > 0, nil
}

// Get tries to find up to X messages in the storage by their key, where X is the length param value
func (m *MongoDefault) Get(key string, limit int) ([]*Message, error) {
	innerContext, cancel := m.connection.Context()
	defer cancel()

	limit64 := int64(limit)

	var messages []*Message
	cursor, err := m.connection.Database.Collection(m.collection).Find(innerContext, primitive.M{"limitkey": key},
		&options.FindOptions{
			Limit: &limit64,
			Sort: primitive.D{
				{"created", 1},
			},
		})

	if err != nil {
		return make([]*Message, 0), err
	}

	err = cursor.All(innerContext, &messages)
	if err != nil {
		return make([]*Message, 0), err
	}

	return messages, nil
}

// GetMessages get records from messages
func (m *MongoDefault) GetMessages(field, key string, limit int) ([]*Message, error) {
	innerContext, cancel := m.connection.Context()
	defer cancel()

	limit64 := int64(limit)

	var messages []*Message
	cursor, err := m.connection.Database.Collection(m.collection).Find(innerContext, primitive.M{field: key},
		&options.FindOptions{
			Limit: &limit64,
			Sort: primitive.D{
				{"created", 1},
			},
		})

	if err != nil {
		return make([]*Message, 0), err
	}

	err = cursor.All(innerContext, &messages)
	if err != nil {
		return make([]*Message, 0), err
	}

	return messages, nil
}

// Count tries to find up to X messages in the storage by their key, where X is the length param value
func (m *MongoDefault) Count(key string, limit int) (int, error) {
	innerContext, cancel := m.connection.Context()
	defer cancel()

	limit64 := int64(limit)

	var messages []*Message
	cursor, err := m.connection.Database.Collection(m.collection).Find(innerContext, primitive.M{"limitkey": key},
		&options.FindOptions{
			Limit: &limit64,
		})

	if err != nil {
		return 0, err
	}

	err = cursor.All(innerContext, &messages)
	if err != nil {
		return 0, err
	}

	return len(messages), nil
}

// CountInGroup get group count
func (m *MongoDefault) CountInGroup(keys []string, limit int) (int, error) {
	if len(keys) == 0 {
		return 0, nil
	}

	innerContext, cancel := m.connection.Context()
	defer cancel()

	limit64 := int64(limit)

	var messages []*Message
	cursor, err := m.connection.Database.Collection(m.collection).Find(innerContext, primitive.M{"groupkey": keys[0]},
		&options.FindOptions{
			Limit: &limit64,
		})

	if err != nil {
		return 0, err
	}

	err = cursor.All(innerContext, &messages)
	if err != nil {
		return 0, err
	}

	return len(messages), nil
}

// GetDistinctFirstItems returns for every distinct limitkey the first record
func (m *MongoDefault) GetDistinctFirstItems() (map[string]*Message, error) {
	items := make(map[string]*Message, 0)

	keys, err := m.getDistinctKeys()
	if err != nil {
		return items, err
	}
	if len(keys) == 0 {
		return items, nil
	}

	for _, key := range keys {
		item, err := m.Get(key, 1)
		if err != nil {
			return items, err
		}
		if len(item) == 0 {
			continue
		}
		items[key] = item[0]
	}

	return items, nil
}

// GetDistinctGroupFirstItems return all saved groups
func (m *MongoDefault) GetDistinctGroupFirstItems() (map[string]*Message, error) {
	items := make(map[string]*Message, 0)

	keys, err := m.getGroupDistinctKeys()
	if err != nil {
		return items, err
	}
	if len(keys) == 0 {
		return items, nil
	}

	for _, key := range keys {
		item, err := m.GetMessages("groupkey", key, 1)
		if err != nil {
			return items, err
		}
		if len(item) == 0 {
			continue
		}
		items[key] = item[0]
	}

	return items, nil
}

// getDistinctKeys returns the distinct limitkey values from collection
func (m *MongoDefault) getDistinctKeys() ([]string, error) {
	innerContext, cancel := m.connection.Context()
	defer cancel()
	cursor, err := m.connection.Database.Collection(m.collection).Distinct(innerContext, "limitkey", primitive.D{})

	if err != nil {
		return make([]string, 0), err
	}
	var keys []string

	for _, value := range cursor {
		keys = append(keys, value.(string))
	}

	return keys, nil
}

func (m *MongoDefault) getGroupDistinctKeys() ([]string, error) {
	innerContext, cancel := m.connection.Context()
	defer cancel()
	cursor, err := m.connection.Database.Collection(m.collection).Distinct(innerContext, "groupkeys", primitive.M{
		"groupkey": primitive.M{"$ne": ""},
	})

	if err != nil {
		return make([]string, 0), err
	}
	var keys []string

	for _, value := range cursor {
		keys = append(keys, value.(string))
	}

	return keys, nil
}

// getActiveSession always returns the active session
func (m *MongoDefault) getActiveSession() mongo.Session {
	return m.session
}

// DropCollection drops current collection
func (m *MongoDefault) DropCollection() {
	innerContext, cancel := m.connection.Context()
	defer cancel()
	if err := m.connection.Database.Collection(m.collection).Drop(innerContext); err != nil {
		m.logger.Error(fmt.Sprintf("failed drop collection %v", err), logger.Context{"error": err})
	}
}

// NewMongo returns the pointer to new created mongo storage instance
func NewMongo(collection string, logger logger.Logger) *MongoDefault {
	return &MongoDefault{collection: collection, logger: logger}
}
