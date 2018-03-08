package storage

type Storage interface {
	Finder
	Creator
	Updater
	Deleter
}

type Creator interface {
	Create(json interface{}) (string, error)
}

type Deleter interface {
	Delete(id string) (bool, error)
}

type Finder interface {
	Find(id string) (interface{}, error)
}

type Updater interface {
	Update(id string, json interface{}) (string, error)
}
