package router

import (
	"bytes"
	"net/http"
	"net/http/httptest"
	"testing"

	"cron/pkg/storage"
	"github.com/stretchr/testify/assert"
)

func TestRouter(t *testing.T) {
	storage.MongoDB = &storage.MongoDBImplementation{}
	storage.MongoDB.Connect()

	r, _ := http.NewRequest(http.MethodGet, "/status", nil)
	assertResponse(t, r, http.StatusOK, `{"database":true}`)
}

func TestRouterOrigin(t *testing.T) {
	storage.MongoDB = &storage.MongoDBImplementation{}
	storage.MongoDB.Connect()

	r, _ := http.NewRequest(http.MethodGet, "/status", nil)
	r.Header.Add("Origin", "https://example.com")
	assertResponseWithHeaders(t, r, http.StatusOK, `{"database":true}`, map[string]string{
		"Access-Control-Allow-Origin":      "https://example.com",
		"Access-Control-Allow-Methods":     "GET, POST, PUT, PATCH, DELETE, OPTIONS",
		"Access-Control-Allow-Headers":     "Content-Type",
		"Access-Control-Allow-Credentials": "true",
		"Access-Control-Max-Age":           "3600",
	})
}

func TestRouterOptions(t *testing.T) {
	r, _ := http.NewRequest(http.MethodOptions, "/status", nil)
	assertResponse(t, r, http.StatusNoContent, "")
}

func TestRouterNotFound(t *testing.T) {
	r, _ := http.NewRequest(http.MethodGet, "/not-found", nil)
	assertResponse(t, r, http.StatusNotFound, "")
}

func TestRouterNotAllowed(t *testing.T) {
	r, _ := http.NewRequest(http.MethodPost, "/status", nil)
	assertResponse(t, r, http.StatusMethodNotAllowed, "")
}

func TestRouterErrorResponse(t *testing.T) {
	r, _ := http.NewRequest(http.MethodPost, "/crons", bytes.NewReader([]byte("")))
	assertResponse(t, r, http.StatusInternalServerError, `{"message":"Internal Server Error"}`)
}

func TestRouterCustomErrorResponse(t *testing.T) {
	r, _ := http.NewRequest(http.MethodPost, "/crons", bytes.NewReader([]byte(`{"time":"Unknown"}`)))
	assertResponse(t, r, http.StatusBadRequest, `{"message":"Unknown CRON expression!"}`)
}

func TestRouterSuccessResponse(t *testing.T) {
	r, _ := http.NewRequest(http.MethodPost, "/crons", bytes.NewReader([]byte(`{"time":"1 1 1 1 1"}`)))
	assertResponse(t, r, http.StatusOK, "{}")
}

func assertResponse(t *testing.T, r *http.Request, code int, content string) {
	recorder := httptest.NewRecorder()
	Router(Routes()).ServeHTTP(recorder, r)

	assert.Equal(t, code, recorder.Code)

	if len(recorder.Body.String()) > 0 {
		assert.Equal(t, content, recorder.Body.String()[:len(recorder.Body.String())-1])
	}
}

func assertResponseWithHeaders(t *testing.T, r *http.Request, code int, content string, headers map[string]string) {
	recorder := httptest.NewRecorder()
	Router(Routes()).ServeHTTP(recorder, r)

	assert.Equal(t, code, recorder.Code)

	if len(recorder.Body.String()) > 0 {
		assert.Equal(t, content, recorder.Body.String()[:len(recorder.Body.String())-1])
	}

	for key, header := range headers {
		assert.Equal(t, header, recorder.Header()[key][0])
	}
}
