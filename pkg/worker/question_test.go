package worker

import (
	"encoding/json"
	"errors"
	"io"
	"net/http"
	"net/http/httptest"
	"strconv"
	"testing"

	"github.com/hanaboso/pipes/bridge/pkg/enum"
	"github.com/hanaboso/pipes/bridge/pkg/model"
	"github.com/hanaboso/pipes/bridge/pkg/mongo"
	"github.com/stretchr/testify/assert"
	"github.com/stretchr/testify/require"
	"go.mongodb.org/mongo-driver/v2/bson"
)

type fakeUserTaskStore struct {
	id     bson.ObjectID
	err    error
	calls  int
	extras bson.M
	body   string
}

func (f *fakeUserTaskStore) StoreUserTask(dto model.ProcessResult, _, _ string, extras ...bson.M) (bson.ObjectID, error) {
	f.calls++
	f.body = string(dto.Message().GetBody())
	if len(extras) > 0 {
		f.extras = extras[0]
	}

	return f.id, f.err
}

type questionNode struct {
	nullNode
	settings model.NodeSettings
}

func (n questionNode) Settings() model.NodeSettings {
	return n.settings
}

type capturedCallback struct {
	headers http.Header
	body    []byte
}

func newCallbackServer(t *testing.T) (*httptest.Server, *[]capturedCallback) {
	captured := &[]capturedCallback{}
	server := httptest.NewServer(http.HandlerFunc(func(w http.ResponseWriter, r *http.Request) {
		body, _ := io.ReadAll(r.Body)
		*captured = append(*captured, capturedCallback{headers: r.Header.Clone(), body: body})
		w.WriteHeader(http.StatusOK)
	}))
	t.Cleanup(server.Close)

	return server, captured
}

// newSdkServer answers like an SDK AQuestion action: echoes the incoming
// headers, adds the given ones and replaces the body (which the worker must restore).
func newSdkServer(t *testing.T, headers map[string]interface{}) *httptest.Server {
	server := httptest.NewServer(http.HandlerFunc(func(w http.ResponseWriter, r *http.Request) {
		var in model.MessageDto
		_ = json.NewDecoder(r.Body).Decode(&in)
		if in.Headers == nil {
			in.Headers = map[string]interface{}{}
		}
		in.Headers[enum.Header_ResultCode] = "0"
		for k, v := range headers {
			in.Headers[k] = v
		}
		out, _ := json.Marshal(model.MessageDto{Body: `{"changed":true}`, Headers: in.Headers})
		w.WriteHeader(http.StatusOK)
		_, _ = w.Write(out)
	}))
	t.Cleanup(server.Close)

	return server
}

func pulseDto(replyTo string) *model.ProcessMessage {
	dto := prepDto()
	dto.Body = []byte(`{"orderId":1}`)
	dto.SetHeader(enum.Header_Source, enum.Source_Pulse)
	dto.SetHeader(enum.Header_Identity, "user-1")
	dto.SetHeader(enum.Header_ReplyTo, replyTo)
	dto.SetHeader(enum.Header_ReplyId, "reply-1")

	return dto
}

func TestQuestion_NotPulse_PassesThrough(t *testing.T) {
	store := &fakeUserTaskStore{}
	callback, captured := newCallbackServer(t)
	dto := pulseDto(callback.URL)
	dto.SetHeader(enum.Header_Source, enum.Source_UI)

	result := Question{mongodb: store}.BeforeProcess(questionNode{}, dto)

	assert.True(t, result.IsOk())
	assert.Equal(t, "", dto.GetHeaderOrDefault(enum.Header_ResultCode, ""))
	assert.Equal(t, 0, store.calls)
	assert.Empty(t, *captured)
}

func TestQuestion_PulseWithoutIdentity_PassesThrough(t *testing.T) {
	store := &fakeUserTaskStore{}
	dto := pulseDto("")
	dto.DeleteHeader(enum.Header_Identity)

	result := Question{mongodb: store}.BeforeProcess(questionNode{}, dto)

	assert.True(t, result.IsOk())
	assert.Equal(t, 0, store.calls)
}

func TestQuestion_Skip_PassesThrough(t *testing.T) {
	store := &fakeUserTaskStore{}
	callback, captured := newCallbackServer(t)
	sdk := newSdkServer(t, map[string]interface{}{enum.Header_QuestionSkip: "1"})
	dto := pulseDto(callback.URL)
	node := questionNode{settings: model.NodeSettings{Url: sdk.URL, ActionPath: "question/action"}}

	result := Question{mongodb: store}.BeforeProcess(node, dto)

	assert.True(t, result.IsOk())
	assert.Equal(t, `{"orderId":1}`, string(dto.GetBody()))
	assert.Equal(t, "", dto.GetHeaderOrDefault(enum.Header_QuestionSkip, ""))
	assert.Equal(t, 0, store.calls)
	assert.Empty(t, *captured)
}

func TestQuestion_Park_StoresTaskAndNotifiesPulse(t *testing.T) {
	id := bson.NewObjectID()
	store := &fakeUserTaskStore{id: id}
	callback, captured := newCallbackServer(t)
	sdk := newSdkServer(t, map[string]interface{}{
		enum.Header_QuestionText:   "Which warehouse?",
		enum.Header_QuestionSchema: `{"type":"object","required":["warehouse"],"properties":{"warehouse":{"type":"string"}}}`,
	})
	dto := pulseDto(callback.URL)
	node := questionNode{settings: model.NodeSettings{Url: sdk.URL, ActionPath: "question/action"}}

	result := Question{mongodb: store}.BeforeProcess(node, dto)

	assert.Equal(t, enum.ProcessStatus_Pending, result.Status())
	assert.Equal(t, "", dto.GetHeaderOrDefault(enum.Header_QuestionText, ""))
	assert.Equal(t, "", dto.GetHeaderOrDefault(enum.Header_QuestionSchema, ""))

	require.Equal(t, 1, store.calls)
	assert.Equal(t, `{"orderId":1}`, store.body)
	assert.Equal(t, mongo.Type_Question, store.extras["type"])
	assert.Equal(t, "user-1", store.extras["identity"])
	assert.Equal(t, "Which warehouse?", store.extras["question"])
	assert.Equal(t, "reply-1", store.extras["replyId"])
	assert.Equal(t, "object", store.extras["schema"].(map[string]any)["type"])

	require.Len(t, *captured, 1)
	cb := (*captured)[0]
	assert.Equal(t, "question", cb.headers.Get(enum.Header_PulseEvent))
	assert.Equal(t, "reply-1", cb.headers.Get(enum.Header_ReplyId))
	assert.Equal(t, "application/json", cb.headers.Get("Content-Type"))
	assert.JSONEq(t, `{
		"kind":"question",
		"task_id":"`+id.Hex()+`",
		"question":"Which warehouse?",
		"schema":{"type":"object","required":["warehouse"],"properties":{"warehouse":{"type":"string"}}}
	}`, string(cb.body))
}

func TestQuestion_Park_InvalidSchemaFallsBackToEmptyObject(t *testing.T) {
	store := &fakeUserTaskStore{id: bson.NewObjectID()}
	callback, captured := newCallbackServer(t)
	dto := pulseDto(callback.URL)
	// No SDK configured: headers already on the message are used as-is.
	dto.SetHeader(enum.Header_QuestionText, "Why?")
	dto.SetHeader(enum.Header_QuestionSchema, `not json`)

	result := Question{mongodb: store}.BeforeProcess(questionNode{}, dto)

	assert.Equal(t, enum.ProcessStatus_Pending, result.Status())
	require.Len(t, *captured, 1)
	var event map[string]any
	require.NoError(t, json.Unmarshal((*captured)[0].body, &event))
	assert.Equal(t, map[string]any{}, event["schema"])
	assert.Equal(t, map[string]any{}, store.extras["schema"])
}

func TestQuestion_Park_StoreError(t *testing.T) {
	store := &fakeUserTaskStore{err: errors.New("mongo down")}
	callback, captured := newCallbackServer(t)
	dto := pulseDto(callback.URL)

	result := Question{mongodb: store}.BeforeProcess(questionNode{}, dto)

	assert.Equal(t, enum.ProcessStatus_Error, result.Status())
	assert.Empty(t, *captured)
}

func decidedDto(state string) *model.ProcessMessage {
	dto := pulseDto("")
	dto.SetHeader(enum.Header_UserTaskState, state)
	dto.SetHeader(enum.Header_PulseDecision, state)

	return dto
}

func TestQuestion_ResumeAccept_Forwards(t *testing.T) {
	store := &fakeUserTaskStore{}
	dto := decidedDto(enum.UserTask_Accept)
	dto.Body = []byte(`{"orderId":1,"warehouse":"A"}`)

	result := Question{mongodb: store}.BeforeProcess(questionNode{}, dto)

	assert.True(t, result.IsOk())
	assert.Equal(t, "", dto.GetHeaderOrDefault(enum.Header_UserTaskState, ""))
	assert.Equal(t, "", dto.GetHeaderOrDefault(enum.Header_PulseDecision, ""))
	assert.Equal(t, "", dto.GetHeaderOrDefault(enum.Header_ResultCode, ""))
	assert.Equal(t, `{"orderId":1,"warehouse":"A"}`, string(dto.GetBody()))
	assert.Equal(t, 0, store.calls)
}

func TestQuestion_ResumeReject_StopsRun(t *testing.T) {
	store := &fakeUserTaskStore{}
	dto := decidedDto(enum.UserTask_Reject)

	result := Question{mongodb: store}.BeforeProcess(questionNode{}, dto)

	assert.True(t, result.IsOk())
	assert.Equal(t, "", dto.GetHeaderOrDefault(enum.Header_UserTaskState, ""))
	assert.Equal(t, strconv.Itoa(enum.ResultCode_DoNotContinue), dto.GetHeaderOrDefault(enum.Header_ResultCode, ""))
	assert.Equal(t, 0, store.calls)
}

func TestQuestion_TrashRetry_AsksAgain(t *testing.T) {
	store := &fakeUserTaskStore{id: bson.NewObjectID()}
	callback, captured := newCallbackServer(t)
	dto := pulseDto(callback.URL)
	dto.SetHeader(enum.Header_UserTaskState, enum.UserTask_Accept)
	dto.SetHeader(enum.Header_QuestionText, "Which warehouse?")

	result := Question{mongodb: store}.BeforeProcess(questionNode{}, dto)

	assert.Equal(t, enum.ProcessStatus_Pending, result.Status())
	assert.Equal(t, 1, store.calls)
	assert.Equal(t, "", dto.GetHeaderOrDefault(enum.Header_UserTaskState, ""))
	assert.Len(t, *captured, 1)
}

func TestQuestion_TrashReject_StopsRun(t *testing.T) {
	store := &fakeUserTaskStore{}
	dto := pulseDto("")
	dto.SetHeader(enum.Header_UserTaskState, enum.UserTask_Reject)

	result := Question{mongodb: store}.BeforeProcess(questionNode{}, dto)

	assert.True(t, result.IsOk())
	assert.Equal(t, strconv.Itoa(enum.ResultCode_DoNotContinue), dto.GetHeaderOrDefault(enum.Header_ResultCode, ""))
	assert.Equal(t, 0, store.calls)
}
