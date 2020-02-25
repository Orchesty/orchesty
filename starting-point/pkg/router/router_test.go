package router

import (
	"bytes"
	"net/http"
	"net/http/httptest"
	"testing"

	"github.com/stretchr/testify/assert"
	"starting-point/pkg/storage"
)

func TestRouter(t *testing.T) {
	storage.Mongo = &MongoMockConnected{}

	r, _ := http.NewRequest("GET", "/status", nil)
	assertResponse(t, r, 200, `{"database":true}`)
}

func TestNotFound(t *testing.T) {
	r, _ := http.NewRequest("GET", "/notFound", nil)
	assertResponse(t, r, 404, "")
}

func TestNotAllowed(t *testing.T) {
	r, _ := http.NewRequest("POST", "/status", nil)
	assertResponse(t, r, 405, "")
}

func TestErrResponse(t *testing.T) {
	r, _ := http.NewRequest("POST", "/topologies/bbb/nodes/aaa/run", bytes.NewReader([]byte("aaa")))
	assertResponse(t, r, 400, "{\"message\":\"Content is not valid!\"}")
}

func assertResponse(t *testing.T, r *http.Request, code int, content string) {
	res := httptest.NewRecorder()
	Router(nil).ServeHTTP(res, r)

	assert.Equal(t, res.Code, code)
	if len(res.Body.String()) > 0 {
		assert.Equal(t, content, res.Body.String()[:len(res.Body.String())-1])
	}
}

func assertResponseWithHeaders(t *testing.T, r *http.Request, code int, content string, headers map[string]string) {
	res := httptest.NewRecorder()
	Router(nil).ServeHTTP(res, r)

	assert.Equal(t, res.Code, code)
	if len(res.Body.String()) > 0 {
		assert.Equal(t, content, res.Body.String()[:len(res.Body.String())-1])
	}

	for key, val := range headers {
		assert.Equal(t, val, res.Header()[key][0])
	}
}
