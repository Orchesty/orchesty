package storage

import "gopkg.in/mgo.v2/bson"

// Storage represents the DB
type Storage interface {
	CheckerSaver
	Remover
	Finder
}

// CheckerSaver represents the part of storage used for easy checking and saving
type CheckerSaver interface {
	Checker
	Saver
}

// Checker should be used for getting know if message can be processed
type Checker interface {
	// CanHandle returns true if message can be processed
	CanHandle(key string, time int, val int) (bool, error)
}

// Saver should be used for adding items to storage
type Saver interface {
	// Save persists message to the storage and returns it's storage key
	Save(m *Message) (string, error)
}

// Remover should be used for removing items from storage
type Remover interface {
	// Remove removes the document by it's unique object id
	Remove(key string, id bson.ObjectId) (bool, error)
	// ClearCacheItem remove item in cache
	ClearCacheItem(key string, val int) bool
}

// Finder should be used for searching in storage
type Finder interface {
	DistinctFinder
	// Exists returns true if any message with given key is present in storage
	Exists(key string) (bool, error)
	// Returns the message or error if no message was found
	Get(key string, length int) ([]*Message, error)
	// Count returns the number of messages with given key
	Count(key string) (int, error)
}

// DistinctFinder is used for searching distinct items
type DistinctFinder interface {
	// GetDistinctFirstItems returns for every distinct limitkey the first record
	GetDistinctFirstItems() (map[string]*Message, error)
}
