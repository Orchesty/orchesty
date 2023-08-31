package storage

import (
	"net/http"
	"testing"

	"cron/pkg/config"
	"cron/pkg/utils"
	"github.com/hanaboso/go-mongodb"
	"github.com/stretchr/testify/assert"
)

var testCron = Cron{
	Topology: "topology",
	Node:     "node",
	Time:     "1 1 1 1 1",
	Command:  "command",
}

var testAnotherCron = Cron{
	Topology: "topology",
	Node:     "node",
	Time:     "2 2 2 2 2",
	Command:  "anotherCommand",
}

var testCronAnother = Cron{
	Topology: "anotherTopology",
	Node:     "anotherNode",
	Time:     "1 1 1 1 1",
	Command:  "anotherCommand",
}

var testAnotherCronAnother = Cron{
	Topology: "anotherTopology",
	Node:     "anotherNode",
	Time:     "2 2 2 2 2",
	Command:  "anotherCommandAnother",
}

var testUnknownCron = Cron{
	Topology: "topology",
	Node:     "node",
	Time:     "time",
	Command:  "command",
}

func TestMongoDB(t *testing.T) {
	MongoDB.Connect()
	assert.Equal(t, true, MongoDB.IsConnected())

	MongoDB.Disconnect()
	assert.Equal(t, false, MongoDB.IsConnected())
}

func TestGetAll(t *testing.T) {
	setUp()

	_, _ = MongoDB.Create(&testCron)

	crons, err := MongoDB.GetAll()

	assert.Nil(t, err)
	assert.Equal(t, 1, len(crons))
	assert.Equal(t, []Cron{testCron}, crons)
}

func TestCreate(t *testing.T) {
	setUp()

	_, err := MongoDB.Create(&testCron)

	assert.Nil(t, err)

	crons, _ := MongoDB.GetAll()

	assert.Equal(t, 1, len(crons))
	assert.Equal(t, []Cron{testCron}, crons)
}

func TestCreateUnknownCron(t *testing.T) {
	setUp()

	_, err := MongoDB.Create(&testUnknownCron)

	assert.Equal(t, &utils.Error{
		Code:    http.StatusBadRequest,
		Message: "Unknown CRON expression!",
	}, err)

	crons, _ := MongoDB.GetAll()

	assert.Equal(t, 0, len(crons))
}

func TestUpdate(t *testing.T) {
	setUp()

	_, _ = MongoDB.Create(&testCron)
	_, err := MongoDB.Update(&testAnotherCron)

	assert.Nil(t, err)

	crons, _ := MongoDB.GetAll()

	assert.Equal(t, 1, len(crons))
	testCrons := []Cron{testAnotherCron}
	testCrons[0].ID = crons[0].ID
	assert.Equal(t, testCrons, crons)
}

func TestUpdateNotFound(t *testing.T) {
	setUp()

	_, err := MongoDB.Update(&testCron)

	assert.Equal(t, &utils.Error{
		Code:    http.StatusNotFound,
		Message: "Unknown CRON!",
	}, err)

	crons, _ := MongoDB.GetAll()

	assert.Equal(t, 0, len(crons))
}

func TestUpdateUnexpectedCron(t *testing.T) {
	setUp()

	_, _ = MongoDB.Create(&testCron)
	_, err := MongoDB.Update(&testUnknownCron)

	assert.Equal(t, &utils.Error{
		Code:    http.StatusBadRequest,
		Message: "Unknown CRON expression!",
	}, err)

	crons, _ := MongoDB.GetAll()

	assert.Equal(t, 1, len(crons))
	testCrons := []Cron{testCron}
	testCrons[0].ID = crons[0].ID
	assert.Equal(t, testCrons, crons)
}

func TestUpsertCreate(t *testing.T) {
	setUp()

	_, err := MongoDB.Upsert(&testCron)

	assert.Nil(t, err)

	crons, _ := MongoDB.GetAll()

	assert.Equal(t, 1, len(crons))
	testCrons := []Cron{testCron}
	testCrons[0].ID = crons[0].ID
	assert.Equal(t, testCrons, crons)
}

func TestUpsertCreateUnknownCron(t *testing.T) {
	setUp()

	_, err := MongoDB.Upsert(&testUnknownCron)

	assert.Equal(t, &utils.Error{
		Code:    http.StatusBadRequest,
		Message: "Unknown CRON expression!",
	}, err)

	crons, _ := MongoDB.GetAll()

	assert.Equal(t, 0, len(crons))
}

func TestUpsertUpdate(t *testing.T) {
	setUp()

	_, _ = MongoDB.Create(&testCron)

	_, err := MongoDB.Upsert(&testAnotherCron)

	assert.Nil(t, err)

	crons, _ := MongoDB.GetAll()

	assert.Equal(t, 1, len(crons))
	testCrons := []Cron{testAnotherCron}
	testCrons[0].ID = crons[0].ID
	assert.Equal(t, testCrons, crons)
}

func TestUpsertUpdateUnexpectedCron(t *testing.T) {
	setUp()

	_, _ = MongoDB.Create(&testCron)
	_, err := MongoDB.Update(&testUnknownCron)

	assert.Equal(t, &utils.Error{
		Code:    http.StatusBadRequest,
		Message: "Unknown CRON expression!",
	}, err)

	crons, _ := MongoDB.GetAll()

	assert.Equal(t, 1, len(crons))
	testCrons := []Cron{testCron}
	testCrons[0].ID = crons[0].ID
	assert.Equal(t, testCrons, crons)
}

func TestDelete(t *testing.T) {
	setUp()

	_, _ = MongoDB.Create(&testCron)

	_, err := MongoDB.Delete(&Cron{
		Topology: "topology",
		Node:     "node",
	})

	assert.Nil(t, err)

	crons, _ := MongoDB.GetAll()

	assert.Equal(t, 0, len(crons))
}

func TestBatchCreate(t *testing.T) {
	setUp()

	_, err := MongoDB.BatchCreate([]Cron{testCron, testCronAnother})

	assert.Nil(t, err)

	crons, _ := MongoDB.GetAll()

	assert.Equal(t, 2, len(crons))
	testCrons := []Cron{testCron, testCronAnother}
	testCrons[0].ID = crons[0].ID
	testCrons[1].ID = crons[1].ID
	assert.Equal(t, testCrons, crons)
}

func TestBatchCreateUnknownCron(t *testing.T) {
	setUp()

	_, err := MongoDB.BatchCreate([]Cron{testCron, testUnknownCron})

	assert.Equal(t, &utils.Error{
		Code:    http.StatusBadRequest,
		Message: "Unknown CRON expression!",
	}, err)

	crons, _ := MongoDB.GetAll()

	assert.Equal(t, 0, len(crons))
}

func TestBatchUpdate(t *testing.T) {
	setUp()

	_, _ = MongoDB.BatchCreate([]Cron{testCron, testCronAnother})
	_, err := MongoDB.BatchUpdate([]Cron{testAnotherCron, testAnotherCronAnother})

	assert.Nil(t, err)

	crons, _ := MongoDB.GetAll()

	assert.Equal(t, 2, len(crons))
	testCrons := []Cron{testAnotherCron, testAnotherCronAnother}
	testCrons[0].ID = crons[0].ID
	testCrons[1].ID = crons[1].ID
	assert.Equal(t, testCrons, crons)
}

func TestBatchUpdateNotFound(t *testing.T) {
	setUp()

	_, err := MongoDB.BatchUpdate([]Cron{testCron, testCronAnother})

	assert.Equal(t, &utils.Error{
		Code:    http.StatusNotFound,
		Message: "Unknown CRON!",
	}, err)

	crons, _ := MongoDB.GetAll()

	assert.Equal(t, 0, len(crons))
}

func TestBatchUpdateUnexpectedCron(t *testing.T) {
	setUp()

	_, _ = MongoDB.BatchCreate([]Cron{testCron, testCronAnother})
	_, err := MongoDB.BatchUpdate([]Cron{testAnotherCron, testUnknownCron})

	assert.Equal(t, &utils.Error{
		Code:    http.StatusBadRequest,
		Message: "Unknown CRON expression!",
	}, err)

	crons, _ := MongoDB.GetAll()

	assert.Equal(t, 2, len(crons))
	testCrons := []Cron{testAnotherCron, testCronAnother}
	testCrons[0].ID = crons[0].ID
	testCrons[1].ID = crons[1].ID
	assert.Equal(t, testCrons, crons)
}

func TestBatchUpsert(t *testing.T) {
	setUp()

	_, _ = MongoDB.Create(&testCron)
	_, err := MongoDB.BatchUpsert([]Cron{testAnotherCron, testCronAnother})

	assert.Nil(t, err)

	crons, _ := MongoDB.GetAll()

	assert.Equal(t, 2, len(crons))
	testCrons := []Cron{testAnotherCron, testCronAnother}
	testCrons[0].ID = crons[0].ID
	testCrons[1].ID = crons[1].ID
	assert.Equal(t, testCrons, crons)
}

func TestBatchUpsertCreate(t *testing.T) {
	setUp()

	_, err := MongoDB.BatchUpsert([]Cron{testCron, testCronAnother})

	assert.Nil(t, err)

	crons, _ := MongoDB.GetAll()

	assert.Equal(t, 2, len(crons))
	testCrons := []Cron{testCron, testCronAnother}
	testCrons[0].ID = crons[0].ID
	testCrons[1].ID = crons[1].ID
	assert.Equal(t, testCrons, crons)
}

func TestBatchUpsertCreateUnknownCron(t *testing.T) {
	setUp()

	_, err := MongoDB.BatchUpsert([]Cron{testCron, testUnknownCron})

	assert.Equal(t, &utils.Error{
		Code:    http.StatusBadRequest,
		Message: "Unknown CRON expression!",
	}, err)

	crons, _ := MongoDB.GetAll()

	assert.Equal(t, 1, len(crons))
	testCrons := []Cron{testCron}
	testCrons[0].ID = crons[0].ID
	assert.Equal(t, testCrons, crons)
}

func TestBatchUpsertUpdate(t *testing.T) {
	setUp()

	_, _ = MongoDB.BatchCreate([]Cron{testCron, testCronAnother})
	_, err := MongoDB.BatchUpsert([]Cron{testAnotherCron, testAnotherCronAnother})

	assert.Nil(t, err)

	crons, _ := MongoDB.GetAll()

	assert.Equal(t, 2, len(crons))
	testCrons := []Cron{testAnotherCron, testAnotherCronAnother}
	testCrons[0].ID = crons[0].ID
	testCrons[1].ID = crons[1].ID
	assert.Equal(t, testCrons, crons)
}

func TestBatchUpsertUpdateUnexpectedCron(t *testing.T) {
	setUp()

	_, _ = MongoDB.BatchCreate([]Cron{testCron, testCronAnother})
	_, err := MongoDB.BatchUpsert([]Cron{testAnotherCron, testUnknownCron})

	assert.Equal(t, &utils.Error{
		Code:    http.StatusBadRequest,
		Message: "Unknown CRON expression!",
	}, err)

	crons, _ := MongoDB.GetAll()

	assert.Equal(t, 2, len(crons))
	testCrons := []Cron{testAnotherCron, testCronAnother}
	testCrons[0].ID = crons[0].ID
	testCrons[1].ID = crons[1].ID
	assert.Equal(t, testCrons, crons)
}

func TestBatchDelete(t *testing.T) {
	setUp()

	_, _ = MongoDB.BatchCreate([]Cron{testCron, testCronAnother})

	_, err := MongoDB.BatchDelete([]Cron{
		{
			Topology: "topology",
			Node:     "node",
		}, {
			Topology: "anotherTopology",
			Node:     "anotherNode",
		},
	})

	assert.Nil(t, err)

	crons, _ := MongoDB.GetAll()

	assert.Equal(t, 0, len(crons))
}

func setUp() {
	MongoDB.Connect()

	connection := mongodb.Connection{}
	connection.Connect(config.MongoDB.Dsn)

	context, cancel := connection.Context()
	defer cancel()

	_ = connection.Database.Drop(context)
}
