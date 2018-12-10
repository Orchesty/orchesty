package utils

import (
	"github.com/mongodb/mongo-go-driver/bson/objectid"
	"github.com/stretchr/testify/assert"
	"net/http"
	"starting-point/pkg/storage"
	"testing"
)

func TestBldCounterHeaders(t *testing.T) {
	headers := http.Header{}
	headers.Add("pf-test", "ok")
	headers.Add("content-type", "application/json")
	topology := storage.Topology{Name: "Topology", ID: objectid.New(), Node: &storage.Node{ID: objectid.New(), Name: "Node"}}
	builder := NewHeaderBuilder(2)

	ret := builder.BldCounterHeaders(topology, headers)

	assert.NotEmpty(t, ret)
	assert.Equal(t, "ok", ret["pf-test"])
	assert.Equal(t, "application/json", ret["content-type"])
	assert.Equal(t, "counter_message", ret["type"])
	assert.Equal(t, "starting_point", ret["app_id"])
	assert.Equal(t, "starting_point", ret["pf-node-name"])
	assert.Equal(t, "starting_point", ret["pf-node-id"])
}

func TestBldHeaders(t *testing.T) {
	headers := http.Header{}
	headers.Add("pf-test", "ok")
	topology := storage.Topology{Name: "Topology", ID: objectid.New(), Node: &storage.Node{ID: objectid.New(), Name: "Node"}}
	builder := NewHeaderBuilder(2)

	ret := builder.BldHeaders(topology, headers, false, false)

	assert.NotEmpty(t, ret)
}

func TestHumanHeaders(t *testing.T) {
	headers := http.Header{}
	headers.Add("pf-test", "ok")
	topology := storage.Topology{Name: "Topology", ID: objectid.New(), Node: &storage.Node{ID: objectid.New(), Name: "Node", HumanTask: &storage.HumanTask{
		ID:            objectid.New(),
		CorrelationID: "correlationID",
		ProcessID:     "processID",
		ContentType:   "contentType",
		SequenceID:    "sequenceID",
		ParentID:      "parentID",
		ParentProcess: "parentProcess",
	}}}
	builder := NewHeaderBuilder(2)

	ret := builder.BldHeaders(topology, headers, true, false)
	assert.NotEmpty(t, ret)
	assert.Equal(t, "0", ret["pf-result-code"])

	ret = builder.BldHeaders(topology, headers, true, true)
	assert.NotEmpty(t, ret)
	assert.Equal(t, "1003", ret["pf-result-code"])
}
