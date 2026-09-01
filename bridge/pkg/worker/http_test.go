package worker

import (
	"net/http"
	"net/http/httptest"
	"testing"

	"github.com/hanaboso/pipes/bridge/pkg/bridge/types"
	"github.com/hanaboso/pipes/bridge/pkg/enum"
	"github.com/hanaboso/pipes/bridge/pkg/model"
	"github.com/stretchr/testify/assert"
)

type httpTestNode struct {
	url string
}

func (n httpTestNode) Followers() types.Publishers { return map[string]types.Publisher{} }
func (n httpTestNode) WorkerType() enum.WorkerType { return enum.WorkerType_Http }
func (n httpTestNode) Settings() model.NodeSettings {
	return model.NodeSettings{Url: n.url, ActionPath: "process"}
}
func (n httpTestNode) Id() string                       { return "node-1" }
func (n httpTestNode) Application() string              { return "" }
func (n httpTestNode) Sdk() string                      { return "" }
func (n httpTestNode) NodeName() string                 { return "node" }
func (n httpTestNode) TopologyName() string             { return "topology" }
func (n httpTestNode) CursorPublisher() types.Publisher { return testPublisher{} }

func expireLock(host, nodeId string) {
	l.mutex.Lock()
	if state, ok := l.hosts[lockerKey(host, nodeId)]; ok {
		state.lockUntil = 0
	}
	l.mutex.Unlock()
}

func httpIdentityDto() *model.ProcessMessage {
	dto := &model.ProcessMessage{
		Headers: make(map[string]interface{}),
		Ack:     func() error { return nil },
		Nack:    func() error { return nil },
	}
	dto.SetHeader(enum.Header_CorrelationId, "corr-1")
	dto.SetHeader(enum.Header_TopologyId, "topo-1")
	dto.SetHeader(enum.Header_NodeId, "node-1")
	return dto
}

func TestHttpBeforeProcess_MalformedResponseRetriesThenPoisons(t *testing.T) {
	ResetForTest()
	setMaxFailures(3)
	defer ResetForTest()

	server := httptest.NewServer(http.HandlerFunc(func(w http.ResponseWriter, _ *http.Request) {
		w.WriteHeader(http.StatusOK)
	}))
	defer server.Close()

	worker := httpBeforeProcess{client: http.Client{}}
	node := httpTestNode{url: server.URL}

	var result model.ProcessResult
	for i := 0; i < 3; i++ {
		result = worker.BeforeProcess(node, httpIdentityDto())

		if i < 2 {
			assert.Equal(t, enum.ProcessStatus_Error, result.Status(), "pre-poison attempts should retry via Error")
		}
		expireLock(server.URL, node.Id())
	}

	assert.Equal(t, enum.ProcessStatus_Trash, result.Status(), "should trash once poisoned")
	assert.Equal(t, "corr-1", result.Message().GetHeaderOrDefault(enum.Header_CorrelationId, ""))
	assert.Equal(t, "topo-1", result.Message().GetHeaderOrDefault(enum.Header_TopologyId, ""))
	assert.Equal(t, "node-1", result.Message().GetHeaderOrDefault(enum.Header_NodeId, ""))
}

func TestHttpBeforeProcess_ServiceUnavailableRetriesThenPoisons(t *testing.T) {
	ResetForTest()
	setMaxFailures(3)
	defer ResetForTest()

	// Mimics the tunnel proxy reporting a momentarily disconnected worker:
	// 503 with a plain-text body and no SDK result-message header.
	server := httptest.NewServer(http.HandlerFunc(func(w http.ResponseWriter, _ *http.Request) {
		w.WriteHeader(http.StatusServiceUnavailable)
		_, _ = w.Write([]byte(`worker "instance-superzoo" not connected`))
	}))
	defer server.Close()

	worker := httpBeforeProcess{client: http.Client{}}
	node := httpTestNode{url: server.URL}

	var result model.ProcessResult
	for i := 0; i < 3; i++ {
		result = worker.BeforeProcess(node, httpIdentityDto())

		if i < 2 {
			assert.Equal(t, enum.ProcessStatus_Error, result.Status(), "503 should retry (repeat) like an unreachable worker")
		}
		expireLock(server.URL, node.Id())
	}

	assert.Equal(t, enum.ProcessStatus_Trash, result.Status(), "should trash once poisoned")
	assert.ErrorContains(t, result.Error(), `worker "instance-superzoo" not connected`, "poison message should surface the upstream reason")
	assert.ErrorContains(t, result.Error(), "poisoned after 3 attempts")
}

func TestHttpBeforeProcess_ErrorStatusNonEnvelopeTrashesDeterministically(t *testing.T) {
	cases := []struct {
		name   string
		status int
	}{
		{"4xx", http.StatusNotFound},
		{"5xx not >500", http.StatusInternalServerError},
	}

	for _, tc := range cases {
		t.Run(tc.name, func(t *testing.T) {
			ResetForTest()
			setMaxFailures(3)
			defer ResetForTest()

			server := httptest.NewServer(http.HandlerFunc(func(w http.ResponseWriter, _ *http.Request) {
				w.WriteHeader(tc.status)
				_, _ = w.Write([]byte("<html>error</html>"))
			}))
			defer server.Close()

			worker := httpBeforeProcess{client: http.Client{}}
			node := httpTestNode{url: server.URL}

			result := worker.BeforeProcess(node, httpIdentityDto())

			assert.Equal(t, enum.ProcessStatus_Trash, result.Status(), "HTTP error status should trash on the first attempt")
			assert.False(t, IsPoisoned(server.URL, node.Id(), "corr-1"), "a single HTTP error response must not poison the correlationId")
			assert.Equal(t, "corr-1", result.Message().GetHeaderOrDefault(enum.Header_CorrelationId, ""))
			assert.Equal(t, "topo-1", result.Message().GetHeaderOrDefault(enum.Header_TopologyId, ""))
			assert.Equal(t, "node-1", result.Message().GetHeaderOrDefault(enum.Header_NodeId, ""))
		})
	}
}
