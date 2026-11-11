package handler

import (
	"testing"
)

func TestStatus(t *testing.T) {
	setUp(t)

	assertResponse(t, "data/status/statusRequest.json", nil, nil, nil, nil)
}

func TestStatusNoContent(t *testing.T) {
	setUp(t)

	assertResponse(t, "data/status/statusNoContentRequest.json", nil, nil, nil, nil)
}

func TestStatusNotFound(t *testing.T) {
	setUp(t)

	assertResponse(t, "data/status/statusNotFoundRequest.json", nil, nil, nil, nil)
}

func TestStatusMethodNotAllowed(t *testing.T) {
	setUp(t)

	assertResponse(t, "data/status/statusMethodNotAllowedRequest.json", nil, nil, nil, nil)
}
