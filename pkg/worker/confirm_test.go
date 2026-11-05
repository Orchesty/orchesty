package worker

import (
	"strconv"
	"testing"

	"github.com/hanaboso/pipes/bridge/pkg/enum"
	"github.com/hanaboso/pipes/bridge/pkg/mongo"
	"github.com/stretchr/testify/assert"
	"github.com/stretchr/testify/require"
	"go.mongodb.org/mongo-driver/v2/bson"
)

type confirmNode struct {
	questionNode
}

func (n confirmNode) FollowerIdsForOutput(output string) string {
	return map[string]string{confirmOutputYes: "yes-node", confirmOutputNo: "no-node"}[output]
}

func TestConfirm_Park_StoresTaskAndNotifiesPulse(t *testing.T) {
	id := bson.NewObjectID()
	store := &fakeUserTaskStore{id: id}
	callback, captured := newCallbackServer(t)
	dto := pulseDto(callback.URL)
	dto.SetHeader(enum.Header_ConfirmQuestion, "Delete order 1?")

	result := Confirm{mongodb: store}.BeforeProcess(confirmNode{}, dto)

	assert.Equal(t, enum.ProcessStatus_Pending, result.Status())
	require.Equal(t, 1, store.calls)
	assert.Equal(t, mongo.Type_Confirm, store.extras["type"])
	assert.Equal(t, "user-1", store.extras["identity"])
	require.Len(t, *captured, 1)
	assert.Equal(t, "confirm", (*captured)[0].headers.Get(enum.Header_PulseEvent))
}

func TestConfirm_NotPulse_TakesYes(t *testing.T) {
	store := &fakeUserTaskStore{}
	dto := pulseDto("")
	dto.SetHeader(enum.Header_Source, enum.Source_UI)

	result := Confirm{mongodb: store}.BeforeProcess(confirmNode{}, dto)

	assert.True(t, result.IsOk())
	assert.Equal(t, "yes-node", dto.GetHeaderOrDefault(enum.Header_ForceTargetQueue, ""))
	assert.Equal(t, 0, store.calls)
}

func TestConfirm_PulseAccept_TakesYes(t *testing.T) {
	store := &fakeUserTaskStore{}
	dto := decidedDto(enum.UserTask_Accept)

	result := Confirm{mongodb: store}.BeforeProcess(confirmNode{}, dto)

	assert.True(t, result.IsOk())
	assert.Equal(t, "yes-node", dto.GetHeaderOrDefault(enum.Header_ForceTargetQueue, ""))
	assert.Equal(t, "", dto.GetHeaderOrDefault(enum.Header_PulseDecision, ""))
	assert.Equal(t, 0, store.calls)
}

func TestConfirm_PulseReject_TakesNo(t *testing.T) {
	store := &fakeUserTaskStore{}
	dto := decidedDto(enum.UserTask_Reject)

	result := Confirm{mongodb: store}.BeforeProcess(confirmNode{}, dto)

	assert.True(t, result.IsOk())
	assert.Equal(t, "no-node", dto.GetHeaderOrDefault(enum.Header_ForceTargetQueue, ""))
	assert.Equal(t, 0, store.calls)
}

// A Trash retry carries only user-task-state: it must ask the user again, never take Yes.
func TestConfirm_TrashRetry_ParksAgain(t *testing.T) {
	store := &fakeUserTaskStore{id: bson.NewObjectID()}
	callback, captured := newCallbackServer(t)
	dto := pulseDto(callback.URL)
	dto.SetHeader(enum.Header_UserTaskState, enum.UserTask_Accept)

	result := Confirm{mongodb: store}.BeforeProcess(confirmNode{}, dto)

	assert.Equal(t, enum.ProcessStatus_Pending, result.Status())
	assert.Equal(t, 1, store.calls)
	assert.Equal(t, "", dto.GetHeaderOrDefault(enum.Header_ForceTargetQueue, ""))
	assert.Len(t, *captured, 1)
}

// A Trash reject discards the run instead of taking the No branch.
func TestConfirm_TrashReject_StopsRun(t *testing.T) {
	store := &fakeUserTaskStore{}
	dto := pulseDto("")
	dto.SetHeader(enum.Header_UserTaskState, enum.UserTask_Reject)

	result := Confirm{mongodb: store}.BeforeProcess(confirmNode{}, dto)

	assert.True(t, result.IsOk())
	assert.Equal(t, strconv.Itoa(enum.ResultCode_DoNotContinue), dto.GetHeaderOrDefault(enum.Header_ResultCode, ""))
	assert.Equal(t, "", dto.GetHeaderOrDefault(enum.Header_ForceTargetQueue, ""))
	assert.Equal(t, 0, store.calls)
}
