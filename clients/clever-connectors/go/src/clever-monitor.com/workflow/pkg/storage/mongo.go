package storage

import (
	"time"
	"fmt"

	"gopkg.in/mgo.v2"
	"gopkg.in/mgo.v2/bson"

	"clever-monitor.com/limiter/pkg/logger"
)

type Mongo struct {
	host       string
	db         string
	collection string
	session    *mgo.Session
	logger     logger.Logger
}

func (s *Mongo) Connect() {
	var err error
	s.logger.Info(fmt.Sprintf("Mongo DB connecting to: %s", s.host), nil)
	s.session, err = mgo.Dial(s.host)

	if err != nil {
		s.logger.Error(fmt.Sprintf("Mongo DB error: %s", err), logger.Context{"error": err})
		s.reconnect()
		return
	}

	s.logger.Info(fmt.Sprintf("Mongo DB is connected to: %s", s.host), nil)
}

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

// Create persists new record to mongo storage and returns it's id
func (s *Mongo) Create(json interface{}) (string, error) {
	return s.upsert(bson.NewObjectId(), json)
}

// Delete removes the document by it's unique id
func (s *Mongo) Delete(id string) (bool, error) {
	c := s.session.DB(s.db).C(s.collection)
	err := c.RemoveId(id)

	if err != nil {
		return false, err
	}

	return true, nil
}

// Find tries to find up the record in storage by it's id key
func (s *Mongo) Find(id string) (interface{}, error) {
	var record interface{}

	c := s.session.DB(s.db).C(s.collection)
	err := c.FindId(id).One(&record)

	if err != nil {
		return nil, err
	}

	return record, nil
}

// Update persists record to mongo storage and returns it's id
func (s *Mongo) Update(id string, json interface{}) (string, error) {
	return s.upsert(bson.ObjectId(id), json)
}

// upsert persists new record to mongo storage and returns it's id
// It updates the record if it already exists
// It creates new record if no record with given id is in storage
func (s *Mongo) upsert(id bson.ObjectId, json interface{}) (string, error) {
	c := s.session.DB(s.db).C(s.collection)
	err := c.Insert(bson.M{"_id": bson.ObjectId(id), "data": json})

	if err != nil {
		return id.String(), err
	}

	return id.String(), nil
}

// DropCollection drops current collection
func (s *Mongo) DropCollection() {
	s.session.DB(s.db).C(s.collection).DropCollection()
}

// Returns the pointer to new created mongo storage instance
func NewMongo(host string, db string, collection string, logger logger.Logger) (*Mongo) {
	return &Mongo{host: host, db: db, collection: collection, logger: logger}
}
