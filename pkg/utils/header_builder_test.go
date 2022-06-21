package utils

import (
	"net/http"
	"testing"

	"github.com/stretchr/testify/assert"
	"go.mongodb.org/mongo-driver/bson/primitive"
	"starting-point/pkg/storage"
)

func TestBldHeaders(t *testing.T) {
	headers := http.Header{}
	headers.Add("pf-test", "ok")
	topology := storage.Topology{Name: "Topology", ID: primitive.NewObjectID(), Node: &storage.Node{ID: primitive.NewObjectID(), Name: "Node"}}
	builder := NewHeaderBuilder(2)

	h, c, d, ti := builder.BldHeaders(topology, headers)

	assert.NotEmpty(t, h)
	assert.NotEmpty(t, c)
	assert.NotEmpty(t, d)
	assert.NotEmpty(t, ti)
}
