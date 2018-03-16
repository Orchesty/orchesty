package storage

import (
	"time"
	"fmt"

	"gopkg.in/mgo.v2"
	"gopkg.in/mgo.v2/bson"

	"clever-monitor/utils/logger"
)

type Mongo struct {
	host       string
	db         string
	collection string
	session    *mgo.Session
	logger     logger.Logger
}

type workflowRecord struct {
	Id   string `bson:"_id,omitempty"`
	Json string `bson:"json"`
}

// Returns the pointer to new created mongo storage instance
func NewMongo(host string, db string, collection string, logger logger.Logger) (*Mongo) {
	return &Mongo{host: host, db: db, collection: collection, logger: logger}
}

// Create persists new record to mongo storage and returns it's id
func (s *Mongo) Create(json string) (string, error) {
	c := s.getActiveSession().DB(s.db).C(s.collection)
	rec := workflowRecord{Id: bson.NewObjectId().Hex(), Json: json}
	err := c.Insert(rec)

	if err != nil {
		return "", err
	}

	return rec.Id, nil
}

// Delete removes the document by it's unique id
func (s *Mongo) Delete(id string) (error) {
	c := s.getActiveSession().DB(s.db).C(s.collection)

	return c.RemoveId(id)
}

// Find tries to find up the record in storage by it's id
func (s *Mongo) Find(id string) (string, error) {
	var record workflowRecord

	c := s.getActiveSession().DB(s.db).C(s.collection)
	err := c.FindId(id).One(&record)

	if err != nil {
		return "", err
	}

	return record.Json, nil
}

// Update persists record to mongo storage and returns it's id
func (s *Mongo) Update(id string, json string) (string, error) {
	c := s.getActiveSession().DB(s.db).C(s.collection)
	rec := workflowRecord{Id: id, Json: json}
	err := c.UpdateId(id, rec)

	if err != nil {
		return "", err
	}

	return rec.Id, nil
}

// DropCollection drops current collection
func (s *Mongo) DropCollection() {
	s.getActiveSession().DB(s.db).C(s.collection).DropCollection()
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

// getActiveSession always returns the active mongo session
func (s *Mongo) getActiveSession() (*mgo.Session) {
	return s.session.Clone()
}
