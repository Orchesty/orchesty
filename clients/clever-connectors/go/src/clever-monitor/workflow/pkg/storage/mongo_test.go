package storage

import (
	"testing"
	"time"
	"os"
	"github.com/stretchr/testify/assert"
	"clever-monitor/utils/env"
	"clever-monitor/utils/logger"
	"gopkg.in/mgo.v2/bson"
)

const (
	mongoDb            = "test"
	editorCollection   = "wf_editor"
	workflowCollection = "wf_workflow"
	fakeObjectId       = "5aa228e1922688649d414d84"
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
	m := NewMongo(mongoHost, mongoDb, editorCollection, workflowCollection, logger.GetNullLogger())

	m.Connect()
	m.ClearStorage()

	testOnNonExistingRecord(t, m)
	testCreateRetrieveAndDelete(t, m)

	// m.ClearStorage()

	endTestCh <- true
}

func testOnNonExistingRecord(t *testing.T, m *Mongo) {
	data, err := m.FindEditorConfig(fakeObjectId)
	assert.Equal(t, "", data)
	assert.Equal(t, "not found", err.Error())

	data, err = m.FindWorkflowConfig(fakeObjectId)
	assert.Equal(t, "", data)
	assert.Equal(t, "not found", err.Error())

	err = m.Delete(fakeObjectId)
	assert.Equal(t, "not found", err.Error())
}

func testCreateRetrieveAndDelete(t *testing.T, m *Mongo) {
	workflows := map[string]string{"first": "workflow content 1", "second": "workflow content 2"}
	edId, err := m.Create("editor content", workflows)
	assert.Len(t, edId, 24)
	assert.True(t, bson.IsObjectIdHex(edId))
	assert.Nil(t, err)

	data, err := m.FindEditorConfig(edId)
	assert.Equal(t, "editor content", data)
	assert.Nil(t, err)

	data, err = m.FindWorkflowConfig(edId)
	assert.Equal(t, "", data)
	assert.Equal(t, "not found", err.Error())

	wConfs, err := m.FindAllWorkflowConfigs(edId)
	assert.Nil(t, err)
	assert.Len(t, wConfs, 2)

	err = m.Delete(edId)
	assert.Nil(t, err)

	data, err = m.FindEditorConfig(edId)
	assert.Equal(t, "", data)
	assert.Equal(t, "not found", err.Error())

	wConfs, err = m.FindAllWorkflowConfigs(edId)
	assert.Nil(t, err)
	assert.Len(t, wConfs, 0)
}
