package service

import (
	"io/ioutil"
	"os/exec"
	"strings"
	"testing"

	"github.com/hanaboso/go-mongodb"
	"github.com/stretchr/testify/assert"

	"cron/pkg/config"
	"cron/pkg/storage"
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

	_, _ = exec.Command("sh", "-c", "su-exec root chmod 777 /etc/crontabs/cron.update").Output()

	Cron.Start()
	Cron.Stop()

	content, _ := ioutil.ReadFile("/etc/crontabs/cron.update")

	assert.Equal(t, "dev", strings.Split(string(content), "\n")[0])
}
