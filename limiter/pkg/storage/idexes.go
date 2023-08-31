package storage

import "gopkg.in/mgo.v2"

// GetIndexes - get indexes to create
func GetIndexes() []mgo.Index {
	return []mgo.Index{
		{
			Key:        []string{"limitkey"},
			Background: true,
		},
		{
			Key:        []string{"limitkey", "created"},
			Background: true,
		},
		{
			Key:        []string{"groupkey"},
			Background: true,
		},
	}
}
