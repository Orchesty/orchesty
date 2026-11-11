package bridge

import (
	"encoding/json"
	"errors"
	"io"
	"net/http"
	"net/http/httptest"
	"strconv"
	"testing"
	"time"

	"github.com/hanaboso/pipes/bridge/pkg/bridge/types"
	"github.com/hanaboso/pipes/bridge/pkg/enum"
	"github.com/hanaboso/pipes/bridge/pkg/model"
	"github.com/hanaboso/pipes/bridge/pkg/worker"
	"github.com/stretchr/testify/assert"
	"github.com/stretchr/testify/require"
)

// start -> conn -> response, conn also feeds a side "notify" branch
func replyTestNode(worker enum.WorkerType, followers ...string) *node {
	n := &node{Node: model.Node{ID: "conn", Name: "conn", Worker: worker}}
	for _, f := range followers {
		n.Node.Followers = append(n.Node.Followers, model.Follower{Id: f})
	}
	n.setReplyPath(map[string]bool{"start": true, "conn": true, "response": true})

	return n
}

func replyDto() *model.ProcessMessage {
	return &model.ProcessMessage{Headers: map[string]interface{}{
		enum.Header_ReplyTo:       "http://caller/reply",
		enum.Header_CorrelationId: "corr-1",
		enum.Header_NodeId:        "conn",
		enum.Header_NodeName:      "conn",
	}}
}

func TestReplyEventKind(t *testing.T) {
	cases := []struct {
		name      string
		worker    enum.WorkerType
		result    func(dto *model.ProcessMessage) model.ProcessResult
		published int
		trashId   string
		kind      string
		reason    string
	}{
		{"trash stored", enum.WorkerType_Http, func(d *model.ProcessMessage) model.ProcessResult { return d.Trash(errors.New("boom")) }, 0, "t1", worker.ReplyEvent_Error, ""},
		{"trash deduplicated", enum.WorkerType_Http, func(d *model.ProcessMessage) model.ProcessResult { return d.Trash(errors.New("boom")) }, 0, "", "", ""},
		{"discard", enum.WorkerType_Http, func(d *model.ProcessMessage) model.ProcessResult { return d.Discard(errors.New("limit")) }, 0, "", worker.ReplyEvent_Error, "limit_exceeded"},
		{"repeat", enum.WorkerType_Http, func(d *model.ProcessMessage) model.ProcessResult {
			d.SetHeader(enum.Header_ResultCode, strconv.Itoa(enum.ResultCode_Repeat))
			return d.Pending()
		}, 0, "", worker.ReplyEvent_Pending, worker.ReplyReason_Repeat},
		{"limiter", enum.WorkerType_Http, func(d *model.ProcessMessage) model.ProcessResult { return d.Pending() }, 0, "", worker.ReplyEvent_Pending, worker.ReplyReason_Limit},
		{"user task", enum.WorkerType_UserTask, func(d *model.ProcessMessage) model.ProcessResult { return d.Pending() }, 0, "", worker.ReplyEvent_Pending, worker.ReplyReason_UserTask},
		{"confirm parks itself", enum.WorkerType_Confirm, func(d *model.ProcessMessage) model.ProcessResult { return d.Pending() }, 0, "", "", ""},
		{"do not continue", enum.WorkerType_Http, func(d *model.ProcessMessage) model.ProcessResult { return d.Stop() }, 0, "", worker.ReplyEvent_Stopped, ""},
		{"response terminal", enum.WorkerType_Response, func(d *model.ProcessMessage) model.ProcessResult { return d.Stop() }, 0, "", "", ""},
		{"continues on main branch", enum.WorkerType_Http, func(d *model.ProcessMessage) model.ProcessResult { return d.Ok() }, 2, "", "", ""},
		{"forwarded only to side branch", enum.WorkerType_Http, func(d *model.ProcessMessage) model.ProcessResult {
			d.SetHeader(enum.Header_ResultCode, strconv.Itoa(enum.ResultCode_ForwardToQueue))
			d.SetHeader(enum.Header_ForceTargetQueue, "notify")
			return d.Ok()
		}, 1, "", worker.ReplyEvent_Stopped, ""},
		{"nothing published", enum.WorkerType_Batch, func(d *model.ProcessMessage) model.ProcessResult { return d.Ok() }, 0, "", worker.ReplyEvent_Stopped, ""},
		{"error is redelivered", enum.WorkerType_Http, func(d *model.ProcessMessage) model.ProcessResult { return d.Error(errors.New("nack")) }, 0, "", "", ""},
	}

	for _, c := range cases {
		t.Run(c.name, func(t *testing.T) {
			n := replyTestNode(c.worker, "response", "notify")
			result := c.result(replyDto())
			kind, reason := n.replyEventKind(result, c.published, types.TargetIds(result.Message()), c.trashId)

			assert.Equal(t, c.kind, kind)
			assert.Equal(t, c.reason, reason)
		})
	}
}

// With a single follower AfterProcess publishes the original message and clears its result-code /
// force-target-queue headers; the targets captured before that must still say "side branch only".
func TestReplyEventKind_SideBranchAfterAfterProcess(t *testing.T) {
	n := replyTestNode(enum.WorkerType_Http, "response", "notify")
	dto := replyDto()
	dto.SetHeader(enum.Header_ResultCode, strconv.Itoa(enum.ResultCode_ForwardToQueue))
	dto.SetHeader(enum.Header_ForceTargetQueue, "notify")
	result := dto.Ok()

	targets := types.TargetIds(result.Message())
	result.Message().ClearHeaders()

	kind, _ := n.replyEventKind(result, 1, targets, "")
	assert.Equal(t, worker.ReplyEvent_Stopped, kind)

	// Without the captured targets the cleared headers look like "all followers", i.e. the main branch
	kind, _ = n.replyEventKind(result, 1, types.TargetIds(result.Message()), "")
	assert.Equal(t, "", kind)
}

func TestNotifyReply_SideBranchIsSilent(t *testing.T) {
	called := make(chan struct{}, 1)
	server := httptest.NewServer(http.HandlerFunc(func(http.ResponseWriter, *http.Request) { called <- struct{}{} }))
	defer server.Close()

	n := &node{Node: model.Node{ID: "notify", Worker: enum.WorkerType_Http}}
	n.setReplyPath(map[string]bool{"conn": true, "response": true})

	dto := replyDto()
	dto.SetHeader(enum.Header_ReplyTo, server.URL)
	n.notifyReply(dto.Trash(errors.New("boom")), 0, nil, "t1")

	select {
	case <-called:
		t.Fatal("side branch must not notify the caller")
	case <-time.After(200 * time.Millisecond):
	}
}

func TestNotifyReply_ErrorEvent(t *testing.T) {
	type received struct {
		kind, replyId string
		event         worker.ReplyEvent
	}
	got := make(chan received, 1)
	server := httptest.NewServer(http.HandlerFunc(func(_ http.ResponseWriter, r *http.Request) {
		body, _ := io.ReadAll(r.Body)
		var event worker.ReplyEvent
		_ = json.Unmarshal(body, &event)
		got <- received{r.Header.Get(enum.Header_PulseEvent), r.Header.Get(enum.Header_ReplyId), event}
	}))
	defer server.Close()

	n := replyTestNode(enum.WorkerType_Http, "response")
	dto := replyDto()
	dto.SetHeader(enum.Header_ReplyTo, server.URL)
	dto.SetHeader(enum.Header_ReplyId, "r-1")
	n.notifyReply(dto.Trash(errors.New("boom")), 0, nil, "t1")

	select {
	case r := <-got:
		assert.Equal(t, worker.ReplyEvent_Error, r.kind)
		assert.Equal(t, "r-1", r.replyId)
		assert.Equal(t, "corr-1", r.event.CorrelationId)
		assert.Equal(t, "conn", r.event.NodeName)
		assert.Equal(t, "boom", r.event.Message)
		assert.Equal(t, "t1", r.event.TrashId)
	case <-time.After(2 * time.Second):
		require.Fail(t, "error event was not delivered")
	}
}
