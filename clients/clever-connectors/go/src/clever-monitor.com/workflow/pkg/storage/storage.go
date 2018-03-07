package storage

type Storage interface {
	Finder
	Creator
	Updater
	Deleter
}

type Creator interface {
	// TODO - remove interface{}
	Create(json interface{}) (string, error)
}

type Deleter interface {
	Delete(id string) (bool, error)
}

type Finder interface {
	// TODO - remove interface{}
	Find(key string) (interface{}, error)
}

type Updater interface {
	// TODO - remove interface{}
	Update(id string, json interface{}) (interface{}, error)
}
