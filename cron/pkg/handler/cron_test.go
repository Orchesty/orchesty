package handler

import (
	"cron/pkg/config"
	"cron/pkg/storage"
	"github.com/hanaboso/go-mongodb"
	"testing"
	"time"
)

func TestCron(t *testing.T) {
	connection := &mongodb.Connection{}
	connection.Connect(config.Mongo.Dsn)
	var mongoStorage = storage.NewStorage(connection, config.Logger, "ApiToken")
	mongoStorage.DropApiTokenCollection()
	mongoStorage.InsertApiToken("orchesty", []string{"topology:run"}, "123")

	setUp(t)

	assertResponse(t, "data/cron/selectEmptyRequest.json", nil, nil, nil, nil)
	assertResponse(t, "data/cron/upsertRequest.json", nil, nil, nil, nil)
	assertResponse(t, "data/cron/selectRequest.json", nil, nil, nil, nil)

	time.Sleep(time.Duration(60-time.Now().Second()) * time.Second) // Intentionally, needed for CRON

	assertResponse(t, "data/cron/deleteRequest.json", nil, nil, nil, nil)
	assertResponse(t, "data/cron/selectEmptyRequest.json", nil, nil, nil, nil)
}

func TestCronBadRequest(t *testing.T) {
	setUp(t)

	assertResponse(t, "data/cron/upsertBadRequestRequest.json", nil, nil, nil, nil)
}

func TestCronInternalServerError(t *testing.T) {
	setUp(t)

	assertResponse(t, "data/cron/upsertInternalServerErrorRequest.json", nil, nil, nil, nil)
}
