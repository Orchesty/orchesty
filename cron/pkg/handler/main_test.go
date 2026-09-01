package handler

import (
	"bytes"
	"encoding/json"
	"fmt"
	"net/http"
	"net/http/httptest"
	"os"
	"strings"
	"testing"

	"github.com/stretchr/testify/assert"
	"github.com/stretchr/testify/require"
	"github.com/tidwall/sjson"

	"cron/pkg/service"
)

type requestFile struct {
	Http    string            `json:"http"`
	Headers map[string]string `json:"headers"`
	Body    interface{}       `json:"body"`
	method  string
	route   string
}

type responseFile struct {
	Http    int               `json:"http"`
	Body    interface{}       `json:"body"`
	Headers map[string]string `json:"headers,omitempty"`
}

type replace map[string]interface{}

func assertResponse(t *testing.T, requestJson string, httpReplacements replace, bodyReplacements replace, headerReplacements replace, responseReplacements replace) {
	if _, err := os.Stat(requestJson); os.IsNotExist(err) {
		t.Fatalf("%s doesn't exist!", requestJson)
	}

	req := parseRequest(t, requestJson, httpReplacements, bodyReplacements, headerReplacements)

	responseJson := strings.Replace(requestJson, "Request.json", "Response.json", 1)
	res, newResFile := parseResponse(t, responseJson, responseReplacements)

	body, err := json.Marshal(req.Body)
	require.Nil(t, err)

	r, _ := http.NewRequest(req.method, req.route, bytes.NewReader(body))

	for key, value := range req.Headers {
		r.Header.Set(key, value)
	}

	result := httptest.NewRecorder()
	Router(Routes()).ServeHTTP(result, r)

	if newResFile {
		res.Http = result.Code
	}

	assert.Equal(t, res.Http, result.Code)
	resultBody := "{}"

	if len(result.Body.String()) > 0 {
		resultBody = result.Body.String()[:len(result.Body.String())-1]
		if newResFile {
			require.Nil(t, json.Unmarshal([]byte(resultBody), &res.Body))
		} else {
			var resBody interface{}
			require.Nil(t, json.Unmarshal([]byte(resultBody), &resBody))
			assert.Equal(t, res.Body, resBody)
		}
	}

	if len(result.Header()) > 0 {
		headers := make(map[string]string, len(result.Header()))

		for key, values := range result.Header() {
			headers[key] = values[0]
		}

		if newResFile {
			res.Headers = headers
		} else {
			if len(res.Headers) > 0 {
				assert.Equal(t, res.Headers, headers)
			}
		}
	}

	if newResFile {
		file, err := json.MarshalIndent(res, "", "    ")
		require.Nil(t, err)
		require.Nil(t, os.WriteFile(responseJson, file, 0644))
	}
}
func parseRequest(t *testing.T, requestJson string, httpReplacements replace, bodyReplacements replace, headerReplacements replace) requestFile {
	dataBytes, err := os.ReadFile(requestJson)
	require.Nil(t, err)
	data := string(dataBytes)

	for key, value := range headerReplacements {
		key = fmt.Sprintf("headers.%s", key)
		data, err = sjson.Set(data, key, value)
		require.Nil(t, err)
	}

	for key, value := range bodyReplacements {
		key = fmt.Sprintf("body.%s", key)
		data, err = sjson.Set(data, key, value)
		require.Nil(t, err)
	}

	var req requestFile
	require.Nil(t, json.Unmarshal([]byte(data), &req))

	httpParts := strings.Split(req.Http, " ")
	require.Equal(t, 2, len(httpParts))
	req.method = httpParts[0]
	req.route = httpParts[1]

	routeParts := strings.Split(req.route, "/")

	for i, part := range routeParts {
		if newVal, ok := httpReplacements[part]; ok {
			routeParts[i] = fmt.Sprintf("%v", newVal)
		}
	}

	req.route = strings.Join(routeParts, "/")

	return req
}

func parseResponse(t *testing.T, responseJson string, responseReplacements replace) (responseFile, bool) {
	_, err := os.Stat(responseJson)

	res := responseFile{}
	newResFile := false

	if os.IsNotExist(err) {
		newResFile = true
	} else {
		dataBytes, err := os.ReadFile(responseJson)
		require.Nil(t, err)
		data := string(dataBytes)

		for key, value := range responseReplacements {
			key = fmt.Sprintf("body.%s", key)
			data, err = sjson.Set(data, key, value)
			require.Nil(t, err)
		}

		require.Nil(t, json.Unmarshal([]byte(data), &res))
	}

	return res, newResFile
}

func setUp(t *testing.T) {
	assert.Nil(t, service.Load())
}
