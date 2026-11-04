package handler

import (
	"io"
	"net/http"
	"net/http/httptest"
	"testing"
	"time"

	"github.com/hanaboso/go-mongodb"
	"github.com/stretchr/testify/assert"

	"cron/pkg/config"
	"cron/pkg/storage"
)

const cronRunTimeout = 75 * time.Second

type startingPointRequest struct {
	method string
	path   string
	apiKey string
	body   string
}

func TestCron(t *testing.T) {
	requests := mockStartingPoint(t)

	connection := &mongodb.Connection{}
	connection.Connect(config.Mongo.Dsn)
	var mongoStorage = storage.NewStorage(connection, config.Logger, "Cron", "ApiToken")
	mongoStorage.DropApiTokenCollection()
	mongoStorage.InsertApiToken("orchesty", []string{"topology:run"}, "123")

	setUp(t)

	assertResponse(t, "data/cron/selectEmptyRequest.json", nil, nil, nil, nil)
	assertResponse(t, "data/cron/upsertRequest.json", nil, nil, nil, nil)
	assertResponse(t, "data/cron/selectRequest.json", nil, nil, nil, nil)

	// Intentionally, needed for CRON
	waitForStartingPointRuns(t, requests, []string{
		"/topologies/Topology One/nodes/Node One/run",
		"/topologies/Topology Two/nodes/Node Two/run",
	})

	assertResponse(t, "data/cron/deleteRequest.json", nil, nil, nil, nil)
	assertResponse(t, "data/cron/selectEmptyRequest.json", nil, nil, nil, nil)
}

func TestCronBadRequest(t *testing.T) {
	setUp(t)

	assertResponse(t, "data/cron/upsertBadRequestRequest.json", nil, nil, nil, nil)
}

func TestCronInternalServerError(t *testing.T) {
	setUp(t)

	assertResponse(t, "data/cron/upsertInternalServerErrorRequest.json", nil, nil, nil, nil)
}

func mockStartingPoint(t *testing.T) <-chan startingPointRequest {
	requests := make(chan startingPointRequest, 16)

	server := httptest.NewServer(http.HandlerFunc(func(w http.ResponseWriter, r *http.Request) {
		body, _ := io.ReadAll(r.Body)

		w.Header().Set("Content-Type", "application/json; charset=utf-8")
		w.WriteHeader(http.StatusOK)
		_, _ = w.Write([]byte("{}"))

		select {
		case requests <- startingPointRequest{r.Method, r.URL.Path, r.Header.Get(config.OrchestyApiKeyHeader), string(body)}:
		default:
		}
	}))

	originalDsn := config.StartingPoint.Dsn
	config.StartingPoint.Dsn = server.URL

	t.Cleanup(func() {
		config.StartingPoint.Dsn = originalDsn
		server.Close()
	})

	return requests
}

func waitForStartingPointRuns(t *testing.T, requests <-chan startingPointRequest, paths []string) {
	t.Helper()

	pending := make(map[string]struct{}, len(paths))

	for _, path := range paths {
		pending[path] = struct{}{}
	}

	deadline := time.NewTimer(cronRunTimeout)
	defer deadline.Stop()

	for len(pending) > 0 {
		select {
		case request := <-requests:
			assert.Contains(t, paths, request.path)
			assert.Equal(t, http.MethodPost, request.method)
			assert.Equal(t, "123", request.apiKey)
			assert.JSONEq(t, "{}", request.body)
			delete(pending, request.path)
		case <-deadline.C:
			t.Errorf("CRON jobs did not run within %s, starting point calls still missing: %v", cronRunTimeout, pending)

			return
		}
	}
}
