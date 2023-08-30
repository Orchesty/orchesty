package utils

import (
	"net/http"
	"testing"

	"github.com/stretchr/testify/assert"
	"go.mongodb.org/mongo-driver/bson/primitive"
	"starting-point/pkg/storage"
)

func TestBldCounterHeaders(t *testing.T) {
	headers := http.Header{}
	headers.Add("pf-test", "ok")
	headers.Add("content-type", "application/json")
	topology := storage.Topology{Name: "Topology", ID: primitive.NewObjectID(), Node: &storage.Node{ID: primitive.NewObjectID(), Name: "Node"}}
	builder := NewHeaderBuilder(2)

	h, c, d, ti := builder.BldCounterHeaders(topology, headers)

	assert.NotEmpty(t, h)
	assert.Equal(t, "ok", h["pf-test"])
	assert.Equal(t, "counter_message", h["type"])
	assert.Equal(t, "starting_point", h["app_id"])
	assert.Equal(t, "starting_point", h["pf-node-name"])
	assert.Equal(t, "starting_point", h["pf-node-id"])
	assert.Equal(t, "1", h["pf-from-starting-point"])
	assert.Equal(t, "application/json", c)
	assert.Equal(t, "application/json", c)
	assert.NotEmpty(t, d)
	assert.NotEmpty(t, ti)
}

func TestBldHeaders(t *testing.T) {
	headers := http.Header{}
	headers.Add("pf-test", "ok")
	topology := storage.Topology{Name: "Topology", ID: primitive.NewObjectID(), Node: &storage.Node{ID: primitive.NewObjectID(), Name: "Node"}}
	builder := NewHeaderBuilder(2)

	h, c, d, ti := builder.BldHeaders(topology, headers, false, false)

	assert.NotEmpty(t, h)
	assert.NotEmpty(t, c)
	assert.NotEmpty(t, d)
	assert.NotEmpty(t, ti)
}

func TestHumanHeaders(t *testing.T) {
	headers := http.Header{}
	headers.Add("pf-test", "ok")
	topology := storage.Topology{Name: "Topology", ID: primitive.NewObjectID(), Node: &storage.Node{ID: primitive.NewObjectID(), Name: "Node", HumanTask: &storage.HumanTask{
		ID:            primitive.NewObjectID(),
		CorrelationID: "correlationID",
		ProcessID:     "ProcessID",
		ContentType:   "contentType",
		SequenceID:    "sequenceID",
		ParentID:      "parentID",
		ParentProcess: "parentProcess",
	}}}
	builder := NewHeaderBuilder(2)

	h, c, d, ti := builder.BldHeaders(topology, headers, true, false)
	assert.NotEmpty(t, h)
	assert.NotEmpty(t, c)
	assert.NotEmpty(t, d)
	assert.NotEmpty(t, ti)
	assert.Equal(t, "0", h["pf-result-code"])

	h, c, d, ti = builder.BldHeaders(topology, headers, true, true)
	assert.NotEmpty(t, h)
	assert.NotEmpty(t, c)
	assert.NotEmpty(t, d)
	assert.NotEmpty(t, ti)
	assert.Equal(t, "0", h["pf-result-code"])
	assert.Equal(t, "1003", h["pf-stop"])
}
