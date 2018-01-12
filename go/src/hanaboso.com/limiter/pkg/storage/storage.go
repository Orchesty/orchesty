package storage

import (
	"gopkg.in/mgo.v2"
	"log"
	"time"
	"fmt"
	"gopkg.in/mgo.v2/bson"
)

type Storage struct {
	host       string
	db         string
	collection string
	session    *mgo.Session
}

func (s *Storage) Connect() {

	var err error
	s.session, err = mgo.Dial(s.host)

	if err != nil {
		log.Println(fmt.Sprintf("Mongo db error: %s", err))
		s.reconnect()
		return
	}

	log.Println(fmt.Sprintf("Mongo DB is connected to %s", s.host))
}

func (s *Storage) disconnect() {
	if s.session != nil {
		s.session.Close()
	}
}

func (s *Storage) reconnect() {
	log.Println("Waiting 1s.")
	time.Sleep(time.Second)
	s.disconnect()
	s.Connect()
}

func (s *Storage) Save(m Message) {
	c := s.session.DB(s.db).C(s.collection)
	err := c.Insert(m)

	if err != nil {
		log.Println(fmt.Sprintf("Storage save error: %s", err))
	}
}

func (s *Storage) Get(key string) (*Message) {
	message := &Message{}
	c := s.session.DB(s.db).C(s.collection)
	err := c.Find(bson.M{"limitKey": key}).One(message)

	if err != nil {
		log.Println(fmt.Sprintf("Storage get error: %s", err))
	}

	return message
}

func NewStorage(host string, db string, collection string) (*Storage) {
	return &Storage{host: host, db: db, collection: collection}
}
