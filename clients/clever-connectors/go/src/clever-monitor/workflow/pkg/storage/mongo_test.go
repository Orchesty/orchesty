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
	eRecord, err := m.FindEditorConfig(fakeObjectId)
	assert.Equal(t, "", eRecord.Json)
	assert.Equal(t, "not found", err.Error())

	wRecord, err := m.FindWorkflowConfig(fakeObjectId)
	assert.Equal(t, "", wRecord.Json)
	assert.Equal(t, "not found", err.Error())

	err = m.Delete(fakeObjectId)
	assert.Equal(t, "not found", err.Error())
}

func testCreateRetrieveAndDelete(t *testing.T, m *Mongo) {
	workflows := []string{0: "workflow content 1", 1: "workflow content 2"}
	edId, err := m.Create("editor content", workflows)
	assert.Len(t, edId, 24)
	assert.True(t, bson.IsObjectIdHex(edId))
	assert.Nil(t, err)

	edRecord, err := m.FindEditorConfig(edId)
	assert.Equal(t, "editor content", edRecord.Json)
	assert.Equal(t, edId, edRecord.Id)
	assert.Nil(t, err)

	// searching by editorId instead of workflowId should fail
	wfRecord, err := m.FindWorkflowConfig(edId)
	assert.Equal(t, "", wfRecord.Json)
	assert.Equal(t, "", wfRecord.Id)
	assert.Equal(t, "", wfRecord.EditorId)
	assert.Equal(t, "not found", err.Error())

	wConfs, err := m.FindAllWorkflowConfigs(edId)
	assert.Nil(t, err)
	assert.Len(t, wConfs, 2)
	wId1 := wConfs[0].Id
	wId2 := wConfs[1].Id

	wfRecord, err = m.FindWorkflowConfig(wId1)
	assert.Equal(t, "workflow content 1", wfRecord.Json)
	assert.Equal(t, wId1, wfRecord.Id)
	assert.Equal(t, edId, wfRecord.EditorId)
	assert.Nil(t,  err)

	wfRecord, err = m.FindWorkflowConfig(wId2)
	assert.Equal(t, "workflow content 2", wfRecord.Json)
	assert.Equal(t, wId2, wfRecord.Id)
	assert.Equal(t, edId, wfRecord.EditorId)
	assert.Nil(t,  err)

	// Delete should remove editorRecord and all associated workflowRecords
	err = m.Delete(edId)
	assert.Nil(t, err)

	edRecord, err = m.FindEditorConfig(edId)
	assert.Equal(t, "", edRecord.Json)
	assert.Equal(t, "not found", err.Error())

	wConfs, err = m.FindAllWorkflowConfigs(edId)
	assert.Nil(t, err)
	assert.Len(t, wConfs, 0)
}
