package loki

import (
	"bytes"
	"context"
	"encoding/json"
	"io"
	"net/http"
	"sort"
	"strconv"
	"strings"
	"sync"
	"time"
)

const (
	pushPath  = "/loki/api/v1/push"
	maxBatch  = 500
	queueSize = 16384
)

var flushInterval = 2 * time.Second

var (
	dsn       string
	client    *http.Client
	queue     chan entry
	done      chan struct{}
	finished  chan struct{}
	startOnce sync.Once
	closeOnce sync.Once
)

type entry struct {
	labels map[string]string
	line   []byte
	ts     time.Time
}

func Init(lokiDsn string) {
	dsn = lokiDsn
	client = &http.Client{Timeout: 5 * time.Second}
	startOnce.Do(func() {
		queue = make(chan entry, queueSize)
		done = make(chan struct{})
		finished = make(chan struct{})
		go worker()
	})
}

func Enabled() bool {
	return dsn != ""
}

func SendLog(labels map[string]string, line []byte) {
	if !Enabled() || queue == nil {
		return
	}

	select {
	case <-done:
		return
	default:
	}

	select {
	case queue <- entry{labels: labels, line: line, ts: time.Now()}:
	default:
	}
}

func Close() {
	if done == nil {
		return
	}

	closeOnce.Do(func() {
		close(done)
	})

	<-finished
}

func worker() {
	defer close(finished)

	ticker := time.NewTicker(flushInterval)
	defer ticker.Stop()

	buf := make([]entry, 0, maxBatch)

	for {
		select {
		case e := <-queue:
			buf = append(buf, e)

			if len(buf) >= maxBatch {
				flush(buf)
				buf = buf[:0]
			}
		case <-ticker.C:
			if len(buf) > 0 {
				flush(buf)
				buf = buf[:0]
			}
		case <-done:
			for {
				select {
				case e := <-queue:
					buf = append(buf, e)
				default:
					flush(buf)
					return
				}
			}
		}
	}
}

func flush(entries []entry) {
	if len(entries) > maxBatch {
		sort.SliceStable(entries, func(i, j int) bool {
			return entries[i].ts.Before(entries[j].ts)
		})
	}

	for start := 0; start < len(entries); start += maxBatch {
		end := start + maxBatch

		if end > len(entries) {
			end = len(entries)
		}

		body, err := buildBatchBody(entries[start:end])

		if err != nil {
			continue
		}

		post(body)
	}
}

func post(body []byte) {
	ctx, cancel := context.WithTimeout(context.Background(), 5*time.Second)
	defer cancel()

	req, err := http.NewRequestWithContext(ctx, http.MethodPost, dsn+pushPath, bytes.NewReader(body))

	if err != nil {
		return
	}

	req.Header.Set("Content-Type", "application/json")

	resp, err := client.Do(req)

	if err != nil {
		return
	}

	_, _ = io.Copy(io.Discard, resp.Body)
	_ = resp.Body.Close()
}

func buildBatchBody(entries []entry) ([]byte, error) {
	type value struct {
		ts   int64
		line string
	}

	type group struct {
		stream map[string]string
		values []value
	}

	groups := make(map[string]*group)
	order := make([]string, 0)

	for _, e := range entries {
		stream := make(map[string]string, len(e.labels))

		for k, v := range e.labels {
			if v == "" {
				continue
			}
			stream[k] = v
		}

		key := labelKey(stream)
		g, ok := groups[key]

		if !ok {
			g = &group{stream: stream}
			groups[key] = g
			order = append(order, key)
		}

		g.values = append(g.values, value{ts: e.ts.UnixNano(), line: string(e.line)})
	}

	streams := make([]pushStream, 0, len(order))

	for _, key := range order {
		g := groups[key]

		sort.SliceStable(g.values, func(i, j int) bool {
			return g.values[i].ts < g.values[j].ts
		})

		values := make([][2]string, len(g.values))

		for i, v := range g.values {
			values[i] = [2]string{strconv.FormatInt(v.ts, 10), v.line}
		}

		streams = append(streams, pushStream{Stream: g.stream, Values: values})
	}

	return json.Marshal(pushPayload{Streams: streams})
}

func labelKey(stream map[string]string) string {
	keys := make([]string, 0, len(stream))

	for k := range stream {
		keys = append(keys, k)
	}

	sort.Strings(keys)

	var b strings.Builder

	for _, k := range keys {
		b.WriteString(k)
		b.WriteByte('=')
		b.WriteString(stream[k])
		b.WriteByte(0)
	}

	return b.String()
}

type pushPayload struct {
	Streams []pushStream `json:"streams"`
}

type pushStream struct {
	Stream map[string]string `json:"stream"`
	Values [][2]string       `json:"values"`
}
