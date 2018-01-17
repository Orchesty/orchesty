package storage

import "gopkg.in/mgo.v2/bson"

type Storage interface {
	CheckerSaver
	Remover
	Finder
}

type CheckerSaver interface {
	Checker
	Saver
}

type Checker interface {
	// Exists returns true if any message with given key is present in storage
	Exists(key string) (bool, error)
}

type Saver interface {
	// Save persists message to the storage and returns it's storage key
	Save(m *Message) (string, error)
}

type Remover interface {
	// Remove removes the document by it's unique object id
	Remove(key string, id bson.ObjectId) (bool, error)
}

type Finder interface {
	// Returns the message or error if no message was found
	Get(key string, length int) ([]*Message, error)
	// Count returns the number of messages with given key
	Count(key string) (int, error)
	// GetDistinctFirstItems returns for every distinct limitkey the first record
	GetDistinctFirstItems() (map[string]*Message, error)
}
