package router

import (
	"bytes"
	"errors"
	"fmt"
	"net/http"
	"testing"

	"cron/pkg/config"
	"cron/pkg/storage"
	"github.com/hanaboso/go-mongodb"
	"go.mongodb.org/mongo-driver/bson/primitive"
	"go.mongodb.org/mongo-driver/mongo"
)

const (
	internalServerError = `{"message":"Internal Server Error"}`
	badRequest          = `{"message":"Unknown CRON expression!"}`
	notFound            = `{"message":"Unknown CRON!"}`
	cron                = `{"time":"1 1 1 1 1"}`
	cronBatch           = `[{"topology":"topology","node":"node","time":"1 1 1 1 1"},{"topology":"anotherTopology","node":"anotherNode","time":"2 2 2 2 2"}]`
	cronUnknown         = `{"time":"Unknown"}`
	cronBatchUnknown    = `[{"topology":"topology","node":"node","time":"1 1 1 1 1"},{"topology":"anotherTopology","node":"anotherNode","time":"Unknown"}]`
	unknownError        = "unknown error"
)

var testCron = storage.Cron{
	Topology: "topology",
	Node:     "node",
	Time:     "1 1 1 1 1",
	Command:  "command",
}

var testCronBatch = []storage.Cron{
	{
		Topology: "topology",
		Node:     "node",
		Time:     "1 1 1 1 1",
		Command:  "command",
	},
	{
		Topology: "anotherTopology",
		Node:     "anotherNode",
		Time:     "2 2 2 2 2",
		Command:  "anotherCommand",
	},
}

type mongoDBMock struct {
	*storage.MongoDBImplementation
}

func (*mongoDBMock) GetAll() ([]storage.Cron, error) {
	return nil, errors.New(unknownError)
}

func (*mongoDBMock) Create(*storage.Cron) (*mongo.InsertOneResult, error) {
	return nil, errors.New(unknownError)
}

func (*mongoDBMock) Update(*storage.Cron) (*mongo.UpdateResult, error) {
	return nil, errors.New(unknownError)
}

func (*mongoDBMock) Upsert(*storage.Cron) (*mongo.UpdateResult, error) {
	return nil, errors.New(unknownError)
}

func (*mongoDBMock) Delete(*storage.Cron) (*mongo.DeleteResult, error) {
	return nil, errors.New(unknownError)
}

func (*mongoDBMock) BatchCreate([]storage.Cron) (*mongo.InsertManyResult, error) {
	return nil, errors.New(unknownError)
}

func (*mongoDBMock) BatchUpdate([]storage.Cron) (*mongo.UpdateResult, error) {
	return nil, errors.New(unknownError)
}

func (*mongoDBMock) BatchUpsert([]storage.Cron) (*mongo.UpdateResult, error) {
	return nil, errors.New(unknownError)
}

func (*mongoDBMock) BatchDelete([]storage.Cron) (*mongo.DeleteResult, error) {
	return nil, errors.New(unknownError)
}

func TestHandleStatus(t *testing.T) {
	setUp()

	r, _ := http.NewRequest(http.MethodGet, "/status", nil)
	assertResponse(t, r, http.StatusOK, `{"database":true}`)
}

func TestHandleGetAll(t *testing.T) {
	setUp()
	result, _ := storage.MongoDB.Create(&testCron)

	r, _ := http.NewRequest(http.MethodGet, "/crons", nil)
	assertResponse(t, r, http.StatusOK, fmt.Sprintf(`[{"id":"%s","topology":"topology","node":"node","time":"1 1 1 1 1","command":"command"}]`, result.InsertedID.(primitive.ObjectID).Hex()))
}

func TestHandleGetAllEmpty(t *testing.T) {
	setUp()

	r, _ := http.NewRequest(http.MethodGet, "/crons", nil)
	assertResponse(t, r, http.StatusOK, "[]")
}

func TestHandleGetAllError(t *testing.T) {
	setUp()
	storage.MongoDB = &mongoDBMock{}

	r, _ := http.NewRequest(http.MethodGet, "/crons", nil)
	assertResponse(t, r, http.StatusInternalServerError, internalServerError)
}

func TestHandleCreate(t *testing.T) {
	setUp()
	config.Config.Logger.Warn(storage.MongoDB)

	r, _ := http.NewRequest(http.MethodPost, "/crons", bytes.NewReader([]byte(cron)))
	assertResponse(t, r, http.StatusOK, "{}")
}

func TestHandleCreateError(t *testing.T) {
	setUp()
	storage.MongoDB = &mongoDBMock{}

	r, _ := http.NewRequest(http.MethodPost, "/crons", bytes.NewReader([]byte(cron)))
	assertResponse(t, r, http.StatusInternalServerError, internalServerError)
}

func TestHandleCreateCronError(t *testing.T) {
	setUp()

	r, _ := http.NewRequest(http.MethodPost, "/crons", bytes.NewReader([]byte(cronUnknown)))
	assertResponse(t, r, http.StatusBadRequest, badRequest)
}

func TestHandleCreateJSONError(t *testing.T) {
	setUp()

	r, _ := http.NewRequest(http.MethodPost, "/crons", bytes.NewReader([]byte("")))
	assertResponse(t, r, http.StatusInternalServerError, internalServerError)
}

func TestHandleUpdate(t *testing.T) {
	setUp()
	_, _ = storage.MongoDB.Create(&testCron)

	r, _ := http.NewRequest(http.MethodPut, "/crons/topology/node", bytes.NewReader([]byte(cron)))
	assertResponse(t, r, http.StatusOK, "{}")
}

func TestHandleUpdateNotFound(t *testing.T) {
	setUp()

	r, _ := http.NewRequest(http.MethodPut, "/crons/topology/node", bytes.NewReader([]byte(cron)))
	assertResponse(t, r, http.StatusNotFound, notFound)
}

func TestHandleUpdateError(t *testing.T) {
	setUp()
	_, _ = storage.MongoDB.Create(&testCron)
	storage.MongoDB = &mongoDBMock{}

	r, _ := http.NewRequest(http.MethodPut, "/crons/topology/node", bytes.NewReader([]byte(cron)))
	assertResponse(t, r, http.StatusInternalServerError, internalServerError)
}

func TestHandleUpdateCronError(t *testing.T) {
	setUp()
	_, _ = storage.MongoDB.Create(&testCron)

	r, _ := http.NewRequest(http.MethodPut, "/crons/topology/node", bytes.NewReader([]byte(cronUnknown)))
	assertResponse(t, r, http.StatusBadRequest, badRequest)
}

func TestHandleUpdateJSONError(t *testing.T) {
	setUp()

	r, _ := http.NewRequest(http.MethodPut, "/crons/topology/node", bytes.NewReader([]byte("")))
	assertResponse(t, r, http.StatusInternalServerError, internalServerError)
}

func TestHandleUpsertCreate(t *testing.T) {
	setUp()

	r, _ := http.NewRequest(http.MethodPatch, "/crons/topology/node", bytes.NewReader([]byte(cron)))
	assertResponse(t, r, http.StatusOK, "{}")
}

func TestHandleUpsertUpdate(t *testing.T) {
	setUp()
	_, _ = storage.MongoDB.Create(&testCron)

	r, _ := http.NewRequest(http.MethodPatch, "/crons/topology/node", bytes.NewReader([]byte(cron)))
	assertResponse(t, r, http.StatusOK, "{}")
}

func TestHandleUpsertError(t *testing.T) {
	setUp()
	_, _ = storage.MongoDB.Create(&testCron)
	storage.MongoDB = &mongoDBMock{}

	r, _ := http.NewRequest(http.MethodPatch, "/crons/topology/node", bytes.NewReader([]byte(cron)))
	assertResponse(t, r, http.StatusInternalServerError, internalServerError)
}

func TestHandleUpsertCronError(t *testing.T) {
	setUp()
	_, _ = storage.MongoDB.Create(&testCron)

	r, _ := http.NewRequest(http.MethodPatch, "/crons/topology/node", bytes.NewReader([]byte(cronUnknown)))
	assertResponse(t, r, http.StatusBadRequest, badRequest)
}

func TestHandleUpsertJSONError(t *testing.T) {
	setUp()

	r, _ := http.NewRequest(http.MethodPatch, "/crons/topology/node", bytes.NewReader([]byte("")))
	assertResponse(t, r, http.StatusInternalServerError, internalServerError)
}

func TestHandleDelete(t *testing.T) {
	setUp()
	_, _ = storage.MongoDB.Create(&testCron)

	r, _ := http.NewRequest(http.MethodDelete, "/crons/topology/node", nil)
	assertResponse(t, r, http.StatusOK, "{}")
}

func TestHandleDeleteError(t *testing.T) {
	setUp()
	storage.MongoDB = &mongoDBMock{}

	r, _ := http.NewRequest(http.MethodDelete, "/crons/topology/node", nil)
	assertResponse(t, r, http.StatusInternalServerError, internalServerError)
}

func TestHandleBatchCreate(t *testing.T) {
	setUp()

	r, _ := http.NewRequest(http.MethodPost, "/crons-batches", bytes.NewReader([]byte(cronBatch)))
	assertResponse(t, r, http.StatusOK, "{}")
}

func TestHandleBatchCreateError(t *testing.T) {
	setUp()
	storage.MongoDB = &mongoDBMock{}

	r, _ := http.NewRequest(http.MethodPost, "/crons-batches", bytes.NewReader([]byte(cronBatch)))
	assertResponse(t, r, http.StatusInternalServerError, internalServerError)
}

func TestHandleBatchCreateCronError(t *testing.T) {
	setUp()

	r, _ := http.NewRequest(http.MethodPost, "/crons-batches", bytes.NewReader([]byte(cronBatchUnknown)))
	assertResponse(t, r, http.StatusBadRequest, badRequest)
}

func TestHandleBatchCreateJSONError(t *testing.T) {
	setUp()

	r, _ := http.NewRequest(http.MethodPost, "/crons-batches", bytes.NewReader([]byte("")))
	assertResponse(t, r, http.StatusInternalServerError, internalServerError)
}

func TestHandleBatchUpdate(t *testing.T) {
	setUp()
	_, _ = storage.MongoDB.BatchCreate(testCronBatch)

	r, _ := http.NewRequest(http.MethodPut, "/crons-batches", bytes.NewReader([]byte(cronBatch)))
	assertResponse(t, r, http.StatusOK, "{}")
}

func TestHandleBatchUpdateNotFound(t *testing.T) {
	setUp()

	r, _ := http.NewRequest(http.MethodPut, "/crons-batches", bytes.NewReader([]byte(cronBatch)))
	assertResponse(t, r, http.StatusNotFound, notFound)
}

func TestHandleBatchUpdateError(t *testing.T) {
	setUp()
	_, _ = storage.MongoDB.BatchCreate(testCronBatch)
	storage.MongoDB = &mongoDBMock{}

	r, _ := http.NewRequest(http.MethodPut, "/crons-batches", bytes.NewReader([]byte(cronBatch)))
	assertResponse(t, r, http.StatusInternalServerError, internalServerError)
}

func TestHandleBatchUpdateCronError(t *testing.T) {
	setUp()
	_, _ = storage.MongoDB.BatchCreate(testCronBatch)

	r, _ := http.NewRequest(http.MethodPut, "/crons-batches", bytes.NewReader([]byte(cronBatchUnknown)))
	assertResponse(t, r, http.StatusBadRequest, badRequest)
}

func TestHandleBatchUpdateJSONError(t *testing.T) {
	setUp()

	r, _ := http.NewRequest(http.MethodPut, "/crons-batches", bytes.NewReader([]byte("")))
	assertResponse(t, r, http.StatusInternalServerError, internalServerError)
}

func TestHandleBatchUpsert(t *testing.T) {
	setUp()
	_, _ = storage.MongoDB.Create(&testCron)

	r, _ := http.NewRequest(http.MethodPatch, "/crons-batches", bytes.NewReader([]byte(cronBatch)))
	assertResponse(t, r, http.StatusOK, "{}")
}

func TestHandleBatchUpsertCreate(t *testing.T) {
	setUp()

	r, _ := http.NewRequest(http.MethodPatch, "/crons-batches", bytes.NewReader([]byte(cronBatch)))
	assertResponse(t, r, http.StatusOK, "{}")
}

func TestHandleBatchUpsertUpdate(t *testing.T) {
	setUp()
	_, _ = storage.MongoDB.BatchCreate(testCronBatch)

	r, _ := http.NewRequest(http.MethodPatch, "/crons-batches", bytes.NewReader([]byte(cronBatch)))
	assertResponse(t, r, http.StatusOK, "{}")
}

func TestHandleBatchUpsertError(t *testing.T) {
	setUp()
	storage.MongoDB = &mongoDBMock{}

	r, _ := http.NewRequest(http.MethodPatch, "/crons-batches", bytes.NewReader([]byte(cronBatch)))
	assertResponse(t, r, http.StatusInternalServerError, internalServerError)
}

func TestHandleBatchUpsertCronError(t *testing.T) {
	setUp()

	r, _ := http.NewRequest(http.MethodPatch, "/crons-batches", bytes.NewReader([]byte(cronBatchUnknown)))
	assertResponse(t, r, http.StatusBadRequest, badRequest)
}

func TestHandleBatchUpsertJSONError(t *testing.T) {
	setUp()

	r, _ := http.NewRequest(http.MethodPatch, "/crons-batches", bytes.NewReader([]byte("")))
	assertResponse(t, r, http.StatusInternalServerError, internalServerError)
}

func TestHandleBatchDelete(t *testing.T) {
	setUp()
	_, _ = storage.MongoDB.BatchCreate(testCronBatch)

	r, _ := http.NewRequest(http.MethodDelete, "/crons-batches", bytes.NewReader([]byte(cronBatch)))
	assertResponse(t, r, http.StatusOK, "{}")
}

func TestHandleBatchDeleteError(t *testing.T) {
	setUp()
	storage.MongoDB = &mongoDBMock{}

	r, _ := http.NewRequest(http.MethodDelete, "/crons-batches", bytes.NewReader([]byte(cronBatch)))
	assertResponse(t, r, http.StatusInternalServerError, internalServerError)
}

func TestHandleBatchDeleteJSONError(t *testing.T) {
	setUp()

	r, _ := http.NewRequest(http.MethodDelete, "/crons-batches", bytes.NewReader([]byte("")))
	assertResponse(t, r, http.StatusInternalServerError, internalServerError)
}

func setUp() {
	storage.MongoDB = &storage.MongoDBImplementation{}
	storage.MongoDB.Connect()

	connection := mongodb.Connection{}
	connection.Connect(config.Config.MongoDB.Dsn)

	context, cancel := connection.Context()
	defer cancel()

	_ = connection.Database.Drop(context)

	connection.Disconnect()
}
