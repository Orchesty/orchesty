package loki

import (
	"encoding/json"
	"io"
	"net/http"
	"net/http/httptest"
	"strconv"
	"sync"
	"testing"
	"time"

	"github.com/stretchr/testify/assert"
	"github.com/stretchr/testify/require"
)

func resetForTest() {
	startOnce = sync.Once{}
	closeOnce = sync.Once{}
	done = nil
	queue = nil
	finished = nil
	dsn = ""
}

func TestBuildBatchBody(t *testing.T) {
	entries := []entry{
		{
			labels:   map[string]string{"correlationId": "corr-1", "levelName": "info", "topologyId": "topo-1", "empty": ""},
			line:     []byte(`{"message":"a"}`),
			metadata: map[string]string{"auditCheckpointId": "product:1234", "empty": ""},
			ts:       time.Unix(0, 1780308580130000000),
		},
		{
			labels: map[string]string{"correlationId": "corr-1", "levelName": "info", "topologyId": "topo-1"},
			line:   []byte(`{"message":"b"}`),
			ts:     time.Unix(0, 1780308580140000000),
		},
		{
			labels: map[string]string{"correlationId": "corr-2", "levelName": "info", "topologyId": "topo-2"},
			line:   []byte(`{"message":"c"}`),
			ts:     time.Unix(0, 1780308580150000000),
		},
	}

	body, err := buildBatchBody(entries)
	require.NoError(t, err)

	var parsed pushPayload
	require.NoError(t, json.Unmarshal(body, &parsed))

	require.Len(t, parsed.Streams, 2)

	byCorr := map[string]pushStream{}

	for _, s := range parsed.Streams {
		byCorr[s.Stream["correlationId"]] = s
	}

	s1 := byCorr["corr-1"]
	assert.Equal(t, map[string]string{
		"correlationId": "corr-1",
		"levelName":     "info",
		"topologyId":    "topo-1",
	}, s1.Stream)
	assert.NotContains(t, s1.Stream, "empty")
	require.Len(t, s1.Values, 2)
	require.Len(t, s1.Values[0], 3)
	assert.Equal(t, "1780308580130000000", s1.Values[0][0])
	assert.Equal(t, `{"message":"a"}`, s1.Values[0][1])
	assert.Equal(t, map[string]any{"auditCheckpointId": "product:1234"}, s1.Values[0][2])
	require.Len(t, s1.Values[1], 2)
	assert.Equal(t, "1780308580140000000", s1.Values[1][0])
	assert.Equal(t, `{"message":"b"}`, s1.Values[1][1])

	s2 := byCorr["corr-2"]
	require.Len(t, s2.Values, 1)
	require.Len(t, s2.Values[0], 2)
	assert.Equal(t, `{"message":"c"}`, s2.Values[0][1])
}

func TestBuildBatchBodySortsStreamByTimestamp(t *testing.T) {
	entries := []entry{
		{labels: map[string]string{"correlationId": "corr-1"}, line: []byte(`{"message":"later"}`), ts: time.Unix(0, 200)},
		{labels: map[string]string{"correlationId": "corr-1"}, line: []byte(`{"message":"earlier"}`), ts: time.Unix(0, 100)},
		{labels: map[string]string{"correlationId": "corr-1"}, line: []byte(`{"message":"middle"}`), ts: time.Unix(0, 150)},
	}

	body, err := buildBatchBody(entries)
	require.NoError(t, err)

	var parsed pushPayload
	require.NoError(t, json.Unmarshal(body, &parsed))
	require.Len(t, parsed.Streams, 1)

	values := parsed.Streams[0].Values
	require.Len(t, values, 3)
	assert.Equal(t, []any{"100", "150", "200"}, []any{values[0][0], values[1][0], values[2][0]})
	assert.Equal(t, `{"message":"earlier"}`, values[0][1])
	assert.Equal(t, `{"message":"middle"}`, values[1][1])
	assert.Equal(t, `{"message":"later"}`, values[2][1])
}

func TestFlushChunksAtMaxBatch(t *testing.T) {
	resetForTest()

	counts := make(chan int, 8)
	srv := httptest.NewServer(http.HandlerFunc(func(w http.ResponseWriter, r *http.Request) {
		b, _ := io.ReadAll(r.Body)
		var parsed pushPayload
		require.NoError(t, json.Unmarshal(b, &parsed))

		total := 0
		for _, s := range parsed.Streams {
			total += len(s.Values)
		}

		counts <- total
		w.WriteHeader(http.StatusNoContent)
	}))
	defer srv.Close()

	dsn = srv.URL
	client = &http.Client{Timeout: 5 * time.Second}

	entries := make([]entry, 0, maxBatch+3)
	for i := 0; i < maxBatch+3; i++ {
		entries = append(entries, entry{
			labels: map[string]string{"correlationId": "corr-1"},
			line:   []byte(`{"x":1}`),
			ts:     time.Unix(0, int64(i)),
		})
	}

	flush(entries)

	assert.Equal(t, maxBatch, <-counts)
	assert.Equal(t, 3, <-counts)

	select {
	case <-counts:
		t.Fatal("unexpected extra push")
	default:
	}
}

func TestFlushOrdersAcrossChunks(t *testing.T) {
	resetForTest()

	pushes := make(chan []string, 8)
	srv := httptest.NewServer(http.HandlerFunc(func(w http.ResponseWriter, r *http.Request) {
		b, _ := io.ReadAll(r.Body)
		var parsed pushPayload
		require.NoError(t, json.Unmarshal(b, &parsed))

		ts := make([]string, 0)
		for _, s := range parsed.Streams {
			for _, v := range s.Values {
				tsValue, ok := v[0].(string)
				require.True(t, ok)
				ts = append(ts, tsValue)
			}
		}

		pushes <- ts
		w.WriteHeader(http.StatusNoContent)
	}))
	defer srv.Close()

	dsn = srv.URL
	client = &http.Client{Timeout: 5 * time.Second}

	total := maxBatch + 3
	entries := make([]entry, 0, total)
	for i := 0; i < total; i++ {
		entries = append(entries, entry{
			labels: map[string]string{"correlationId": "corr-1"},
			line:   []byte(`{"x":1}`),
			ts:     time.Unix(0, int64(total-i)),
		})
	}

	flush(entries)

	ordered := make([]string, 0, total)
	ordered = append(ordered, <-pushes...)
	ordered = append(ordered, <-pushes...)
	require.Len(t, ordered, total)

	for i := 1; i < len(ordered); i++ {
		prev, err := strconv.ParseInt(ordered[i-1], 10, 64)
		require.NoError(t, err)
		cur, err := strconv.ParseInt(ordered[i], 10, 64)
		require.NoError(t, err)
		assert.LessOrEqual(t, prev, cur)
	}
}

func TestSendLogRejectedAfterClose(t *testing.T) {
	resetForTest()
	flushInterval = time.Hour

	got := make(chan struct{}, 1)
	srv := httptest.NewServer(http.HandlerFunc(func(w http.ResponseWriter, r *http.Request) {
		got <- struct{}{}
		w.WriteHeader(http.StatusNoContent)
	}))
	defer srv.Close()

	Init(srv.URL)
	Close()

	SendLog(map[string]string{"correlationId": "x"}, []byte(`{"x":1}`), nil)

	select {
	case <-got:
		t.Fatal("send after Close was delivered")
	case <-time.After(50 * time.Millisecond):
	}
}

func TestSendLogPostsToLoki(t *testing.T) {
	resetForTest()
	flushInterval = 10 * time.Millisecond

	got := make(chan []byte, 1)
	srv := httptest.NewServer(http.HandlerFunc(func(w http.ResponseWriter, r *http.Request) {
		assert.Equal(t, "/loki/api/v1/push", r.URL.Path)
		assert.Equal(t, "application/json", r.Header.Get("Content-Type"))
		b, _ := io.ReadAll(r.Body)
		got <- b
		w.WriteHeader(http.StatusNoContent)
	}))
	defer srv.Close()

	Init(srv.URL)
	SendLog(map[string]string{"correlationId": "corr-1"}, []byte(`{"x":1}`), map[string]string{"auditCheckpointId": "product:1"})

	select {
	case b := <-got:
		var parsed pushPayload
		require.NoError(t, json.Unmarshal(b, &parsed))
		require.Len(t, parsed.Streams, 1)
		assert.Equal(t, "corr-1", parsed.Streams[0].Stream["correlationId"])
		require.Len(t, parsed.Streams[0].Values[0], 3)
		assert.Equal(t, `{"x":1}`, parsed.Streams[0].Values[0][1])
		assert.Equal(t, map[string]any{"auditCheckpointId": "product:1"}, parsed.Streams[0].Values[0][2])
	case <-time.After(2 * time.Second):
		t.Fatal("Loki push was not received")
	}
}

func TestSendLogNoopWhenDisabled(t *testing.T) {
	dsn = ""
	assert.False(t, Enabled())

	SendLog(map[string]string{"correlationId": "x"}, []byte(`{}`), nil)
}
