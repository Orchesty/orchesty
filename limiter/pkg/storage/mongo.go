package storage

import (
	"gopkg.in/mgo.v2"
	"gopkg.in/mgo.v2/bson"

	"limiter/pkg/logger"

	"fmt"
	"time"
)

// Mongo represents the mongo db connection
type Mongo struct {
	host       string
	db         string
	collection string
	session    *mgo.Session
	logger     logger.Logger
}

// Connect creates new connection to mongo DB
func (s *Mongo) Connect() {
	var err error
	s.logger.Info(fmt.Sprintf("Mongo DB connecting to: %s", s.host), nil)
	s.session, err = mgo.DialWithTimeout(s.host, 2500*time.Millisecond)
	if err != nil {
		s.logger.Error(fmt.Sprintf("Mongo DB error: %s", err), logger.Context{"error": err})
		s.reconnect()
		return
	}

	s.session.SetMode(mgo.Monotonic, true)

	s.logger.Info(fmt.Sprintf("Mongo DB is connected to: %s", s.host), nil)
}

// Disconnect closes the mongo db connection
func (s *Mongo) Disconnect() {
	if s.session != nil {
		s.session.Close()
	}
}

func (s *Mongo) reconnect() {
	s.logger.Info("Waiting 1s.", nil)
	time.Sleep(time.Second * 1)
	s.Disconnect()
	s.Connect()
}

// CanHandle just calls Exists method
func (s *Mongo) CanHandle(key string, time int, value int, groupKey string, groupTime int, groupValue int) (bool, error) {
	return s.Exists(key)
}

// Remove removes the document by it's unique id
func (s *Mongo) Remove(key string, id bson.ObjectId) (bool, error) {
	session := s.getActiveSession()
	defer session.Close()

	c := session.DB(s.db).C(s.collection)

	if err := c.RemoveId(id); err != nil {
		return false, err
	}

	return true, nil
}

// ClearCacheItem remove key from memory cache
func (s *Mongo) ClearCacheItem(key string, val int) bool {
	return true
}

// Save persists Message to mongo storage and returns it's limitKey
func (s *Mongo) Save(m *Message) (string, error) {
	session := s.getActiveSession()
	defer session.Close()

	c := session.DB(s.db).C(s.collection)
	if err := c.Insert(m); err != nil {
		return m.LimitKey, err
	}

	return m.LimitKey, nil
}

// CreateIndex - RunCommand run command
func (s *Mongo) CreateIndex(index mgo.Index) error {
	session := s.getActiveSession()
	defer session.Close()

	return session.DB(s.db).C(s.collection).EnsureIndex(index)
}

// Exists return boolean if any document found with given key or returns error if some mongo error occurs
func (s *Mongo) Exists(key string) (bool, error) {
	session := s.getActiveSession()
	defer session.Close()

	c := session.DB(s.db).C(s.collection)

	var messages []*Message
	if err := c.Find(bson.M{"limitkey": key}).Limit(1).All(&messages); err != nil {
		return false, err
	}

	return len(messages) > 0, nil
}

// Get tries to find up to X messages in the storage by their key, where X is the length param value
func (s *Mongo) Get(key string, length int) ([]*Message, error) {
	var messages []*Message
	session := s.getActiveSession()
	defer session.Close()

	c := session.DB(s.db).C(s.collection)

	if err := c.Find(bson.M{"limitkey": key}).Limit(length).Sort("created").Iter().All(&messages); err != nil {
		return make([]*Message, 0), err
	}

	return messages, nil
}

// GetMessages get records from messages
func (s *Mongo) GetMessages(field, key string, length int) ([]*Message, error) {
	var messages []*Message
	session := s.getActiveSession()
	defer session.Close()

	c := session.DB(s.db).C(s.collection)

	if err := c.Find(bson.M{field: key}).Limit(length).Sort("created").Iter().All(&messages); err != nil {
		return make([]*Message, 0), err
	}

	return messages, nil
}

// Count tries to find up to X messages in the storage by their key, where X is the length param value
func (s *Mongo) Count(key string, limit int) (int, error) {
	session := s.getActiveSession()
	defer session.Close()

	c := session.DB(s.db).C(s.collection)

	var messages []*Message
	if err := c.Find(bson.M{"limitkey": key}).Limit(limit).All(&messages); err != nil {
		return 0, err
	}

	return len(messages), nil
}

// CountInGroup get group count
func (s *Mongo) CountInGroup(keys []string, limit int) (int, error) {
	if len(keys) == 0 {
		return 0, nil
	}

	session := s.getActiveSession()
	defer session.Close()

	c := session.DB(s.db).C(s.collection)

	items := make([]interface{}, 0)
	for _, key := range keys {
		items = append(items, bson.M{"groupkey": key})
	}
	var messages []*Message
	if err := c.Find(bson.M{"groupkey": keys[0]}).Limit(limit).All(&messages); err != nil {
		return 0, err
	}

	return len(messages), nil
}

// GetDistinctFirstItems returns for every distinct limitkey the first record
func (s *Mongo) GetDistinctFirstItems() (map[string]*Message, error) {
	items := make(map[string]*Message, 0)

	keys, err := s.getDistinctKeys()
	if err != nil {
		return items, err
	}
	if len(keys) == 0 {
		return items, nil
	}

	for _, key := range keys {
		item, err := s.Get(key, 1)
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
func (s *Mongo) GetDistinctGroupFirstItems() (map[string]*Message, error) {
	items := make(map[string]*Message, 0)

	keys, err := s.getGroupDistinctKeys()
	if err != nil {
		return items, err
	}
	if len(keys) == 0 {
		return items, nil
	}

	for _, key := range keys {
		item, err := s.GetMessages("groupkey", key, 1)
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
func (s *Mongo) getDistinctKeys() ([]string, error) {
	var keys []string
	session := s.getActiveSession()
	defer session.Close()

	c := session.DB(s.db).C(s.collection)

	if err := c.Find(nil).Distinct("limitkey", &keys); err != nil {
		return make([]string, 0), err
	}

	return keys, nil
}

func (s *Mongo) getGroupDistinctKeys() ([]string, error) {
	var keys []string
	session := s.getActiveSession()
	defer session.Close()

	c := session.DB(s.db).C(s.collection)

	if err := c.Find(bson.M{"groupkey": bson.M{"$ne": ""}}).Distinct("groupkey", &keys); err != nil {
		return make([]string, 0), err
	}

	return keys, nil
}

// getActiveSession always returns the active session
func (s *Mongo) getActiveSession() *mgo.Session {
	return s.session.Copy()
}

// DropCollection drops current collection
func (s *Mongo) DropCollection() {
	session := s.getActiveSession()
	defer session.Close()

	if err := session.DB(s.db).C(s.collection).DropCollection(); err != nil {
		s.logger.Error(fmt.Sprintf("failed drop collection %v", err), logger.Context{"error": err})
	}
}

// NewMongo returns the pointer to new created mongo storage instance
func NewMongo(host string, db string, collection string, logger logger.Logger) *Mongo {
	return &Mongo{host: host, db: db, collection: collection, logger: logger}
}
