package storage

type Storage interface {
	Finder
	Creator
	Updater
	Deleter
}

type Creator interface {
	Create(json string) (string, error)
}

type Updater interface {
	Update(id string, json string) (string, error)
}

type Finder interface {
	Find(id string) (string, error)
}

type Deleter interface {
	Delete(id string) (error)
}
