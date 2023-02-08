package router

import (
	"net/http"
)

type responseMock struct{}

func (r responseMock) Header() http.Header {
	return map[string][]string{}
}

func (r responseMock) Write(bytes []byte) (int, error) {
	return 0, nil
}

func (r responseMock) WriteHeader(statusCode int) {}
