package service

import (
	"io/ioutil"
	"testing"

	"cron/pkg/config"
	"cron/pkg/storage"
	"github.com/hanaboso/go-mongodb"
	"github.com/stretchr/testify/assert"
)

func TestCron(t *testing.T) {
	storage.MongoDB = &storage.MongoDBImplementation{}
	storage.MongoDB.Connect()

	connection := mongodb.Connection{}
	connection.Connect(config.MongoDB.Dsn)

	context, cancel := connection.Context()
	defer cancel()

	_ = connection.Database.Drop(context)
	_, _ = storage.MongoDB.Create(&storage.Cron{
		Topology: "topology",
		Node:     "node",
		Time:     "1 1 1 1 1",
		Command:  "command",
	})

	Cron.Start()
	Cron.Stop()

	content, _ := ioutil.ReadFile("/etc/crontabs/root")

	assert.Equal(t, "1 1 1 1 1 command", string(content))
}
