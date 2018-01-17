package storage

import (
	"gopkg.in/mgo.v2"
	"log"
	"time"
	"fmt"
	"gopkg.in/mgo.v2/bson"
)

type Mongo struct {
	host       string
	db         string
	collection string
	session    *mgo.Session
}

func (s *Mongo) Connect() {
	var err error
	s.session, err = mgo.Dial(s.host)

	if err != nil {
		log.Println(fmt.Sprintf("Mongo db error: %s", err))
		s.reconnect()
		return
	}

	log.Println(fmt.Sprintf("Mongo DB is connected to %s", s.host))
}

func (s *Mongo) Disconnect() {
	if s.session != nil {
		s.session.Close()
	}
}

func (s *Mongo) reconnect() {
	log.Println("Waiting 1s.")
	time.Sleep(time.Second)
	s.Disconnect()
	s.Connect()
}

// Exists return boolean if any document found with given key or returns error if some mongo error occurs
func (s *Mongo) Exists(key string) (bool, error) {
	c := s.session.DB(s.db).C(s.collection)
	count, err := c.Find(bson.M{"limitkey": key}).Limit(1).Count()

	if err != nil {
		return false, err
	}

	if count > 0 {
		return true, nil
	}

	return false, nil
}

// Delete removes the document by it's unique id
func (s *Mongo) Remove(key string, id bson.ObjectId) (bool, error) {
	c := s.session.DB(s.db).C(s.collection)
	err := c.RemoveId(id)

	if err != nil {
		return false, err
	}

	return true, nil
}

// Save persists Message to mongo storage and returns it's limitKey
func (s *Mongo) Save(m *Message) (string, error) {
	c := s.session.DB(s.db).C(s.collection)
	err := c.Insert(m)

	if err != nil {
		return m.LimitKey, err
	}

	return m.LimitKey, nil
}

// Get tries to find up to X messages in the storage by their key, where X is the length param value
func (s *Mongo) Get(key string, length int) ([]*Message, error) {
	var messages []*Message
	c := s.session.DB(s.db).C(s.collection)

	err := c.Find(bson.M{"limitkey": key}).Limit(length).Iter().All(&messages)

	if err != nil {
		return make([]*Message, 0), err
	}

	return messages, nil
}

// Get tries to find up to X messages in the storage by their key, where X is the length param value
func (s *Mongo) Count(key string) (int, error) {
	c := s.session.DB(s.db).C(s.collection)

	return c.Find(bson.M{"limitkey": key}).Count()
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

		items[key] = item[0]
	}

	return items, nil
}

// getDistinctKeys returns the distint limitkey values from collection
func (s *Mongo) getDistinctKeys() ([]string, error) {
	var keys []string
	c := s.session.DB(s.db).C(s.collection)
	err := c.Find(nil).Distinct("limitkey", &keys)

	if err != nil {
		return make([]string, 0), err
	}

	return keys, nil
}

// DropCollection drops current collection
func (s *Mongo) DropCollection() {
	s.session.DB(s.db).C(s.collection).DropCollection()
}

// Returns the pointer to new created mongo storage instance
func NewMongo(host string, db string, collection string) (*Mongo) {
	return &Mongo{host: host, db: db, collection: collection}
}
