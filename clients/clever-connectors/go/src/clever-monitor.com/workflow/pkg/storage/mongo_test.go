package storage

import (
	"testing"
	"time"
	"os"
	"github.com/stretchr/testify/assert"
	"clever-monitor.com/utils/env"
	"clever-monitor.com/utils/logger"
	"gopkg.in/mgo.v2/bson"
)

const (
	mongoDb         = "test"
	mongoCollection = "workflow_test"
	fakeObjectId    = "5aa228e1922688649d414d84"
)

// TestMongoMethods checks implementation of storage interface methods against real mongo instance
func TestMongoCRUD(t *testing.T) {
	endTestCh := make(chan bool)

	go func(stopMongoTest chan bool) {
		time.Sleep(time.Second * 1)
		stopMongoTest <- false
	}(endTestCh)

	go runTestCommandsInSeries(t, endTestCh)

	// wait for stopTest message
	result := <-endTestCh
	if result == false {
		assert.Fail(t, "Test timeout")
	}
}

func runTestCommandsInSeries(t *testing.T, endTestCh chan bool) {
	os.Setenv("MONGO_HOST", env.GetEnv("MONGO_HOST", "mongodb"))
	mongoHost := os.Getenv("MONGO_HOST")
	m := NewMongo(mongoHost, mongoDb, mongoCollection, logger.GetNullLogger())

	m.Connect()
	m.session.DB("test").C(mongoCollection).DropCollection()

	m.DropCollection()

	// find non-existing
	data, err := m.Find(fakeObjectId)
	assert.Equal(t, "", data)
	assert.Equal(t, "not found", err.Error())

	// delete non-existing
	err = m.Delete(fakeObjectId)
	assert.Equal(t, "not found", err.Error())

	// update non-existing
	id, err := m.Update(fakeObjectId, "content")
	assert.Equal(t, "", id)
	assert.Equal(t, "not found", err.Error())

	// create new
	id, err = m.Create("content")
	assert.Len(t, id, 24)
	assert.True(t, bson.IsObjectIdHex(id))
	assert.Nil(t, err)

	// find existing
	data, err = m.Find(id)
	assert.Equal(t, "content", data)
	assert.Nil(t, err)

	// update existing
	updId, err := m.Update(id, "updated content")
	assert.Equal(t, id, updId)
	assert.Nil(t, err)

	// find existing
	data, err = m.Find(id)
	assert.Equal(t, "updated content", data)
	assert.Nil(t, err)

	// delete existing
	err = m.Delete(id)
	assert.Nil(t, err)

	// find deleted
	data, err = m.Find(id)
	assert.Equal(t, "", data)
	assert.Equal(t, "not found", err.Error())

	m.DropCollection()

	endTestCh <- true
}
