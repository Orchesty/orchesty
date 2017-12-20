package probe

import (
	"testing"
	"github.com/stretchr/testify/assert"
	"net/http"
	"io/ioutil"
	"bytes"
	"fmt"
)

type httpOKClientMock struct {}

func (h *httpOKClientMock) Do(req *http.Request) (*http.Response, error) {
	resp := http.Response{}
	resp.StatusCode = http.StatusOK
	resp.Body = ioutil.NopCloser(bytes.NewBufferString("Hello World"))

	return &resp, nil
}

// TestCheckOK tests if Check method creates proper http request, receives response and updates BridgeInfo object
func TestCheckOK(t *testing.T) {
	results := make(chan BridgeInfo, 1)
	bridge := BridgeInfo{
		Id:     "test-123",
		NodeId: "test",
		Status: false,
		Url:    "http://localhost:1000/test",
	}

	httpClient := new(httpOKClientMock)
	var checker = HttpChecker{Client: httpClient}

	checker.Check(bridge, results)

	br := <-results

	assert.True(t, br.Status)
	assert.Equal(t, http.StatusOK, br.Code)
	assert.Equal(t, "http://localhost:1000/test", br.Url)
	assert.Equal(t, "Hello World", br.Message)
	assert.Equal(t, "test-123", br.Id)
	assert.Equal(t, "test", br.NodeId)
	assert.Equal(t, "", br.NodeName)
}

type httpNotOKClientMock struct {}

func (h *httpNotOKClientMock) Do(req *http.Request) (*http.Response, error) {
	resp := http.Response{}

	return &resp, fmt.Errorf("some http error")
}

// TestCheckNotOK test if Check method handles error returned by http client
func TestCheckNotOK(t *testing.T) {
	results := make(chan BridgeInfo, 1)
	bridge := BridgeInfo{
		Id:     "test-123",
		NodeId: "test",
		Status: false,
		Url:    "http://localhost:1000/test",
	}

	httpClient := new(httpNotOKClientMock)
	var checker = HttpChecker{Client: httpClient}

	checker.Check(bridge, results)

	br := <-results

	assert.False(t, br.Status)
	assert.Equal(t, http.StatusServiceUnavailable, br.Code)
	assert.Equal(t, "http://localhost:1000/test", br.Url)
	assert.Equal(t, "Error checking bridge: some http error", br.Message)
	assert.Equal(t, "test-123", br.Id)
	assert.Equal(t, "test", br.NodeId)
	assert.Equal(t, "", br.NodeName)
}
