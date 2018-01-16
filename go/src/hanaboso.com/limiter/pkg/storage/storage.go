package storage

import "gopkg.in/mgo.v2/bson"

type Storage interface {
	// Saves message to the storage and returns it's storage key
	Save(m *Message) (string, error)
	// Returns the message or error if no message was found
	Get(key string, length int) ([]*Message, error)
	// Exists returns true if any message with given key is present in storage
	Exists(key string) (bool, error)
	// Delete removes the document by it's unique object id
	Delete(id bson.ObjectId) (bool, error)
	// GetDistinctFirstItems returns for every distinct limitkey the first record
	GetDistinctFirstItems() (map[string]*Message, error)
}
