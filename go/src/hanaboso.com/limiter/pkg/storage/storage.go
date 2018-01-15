package storage

type Storage interface {
	// Saves message to the storage and returns it's storage key
	Save(m *Message) (string, error)
	// Returns the message or error if no message was found
	Get(key string, length int) ([]*Message, error)
	// Exists returns true if any message with given key is present in storage
	Exists(key string) (bool, error)
}
