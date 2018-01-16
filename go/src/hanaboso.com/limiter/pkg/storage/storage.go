package storage

type Storage interface {
	// Saves message to the storage and returns it's storage key
	Save(m *Message) (string, error)
	// Returns the message or error if no message was found
	Get(key string, length int) ([]*Message, error)
	// Exists returns true if any message with given key is present in storage
	Exists(key string) (bool, error)
	// Delete removes the document by it's unique id
	Delete(id string) (bool, error)
	// GetDistinctFirstItems returns for every distinct limitkey the first record
	GetDistinctFirstItems() ([]string, error)
}
