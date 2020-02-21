package metrics

import (
	"testing"

	"github.com/stretchr/testify/assert"
	"go.mongodb.org/mongo-driver/bson/primitive"
	"starting-point/pkg/storage"
)

func TestGetTags(t *testing.T) {
	topology := storage.Topology{Name: "Topology", ID: primitive.NewObjectID(), Node: &storage.Node{ID: primitive.NewObjectID(), Name: "Node"}}
	r := GetTags(topology, "123")

	assert.IsType(t, string(0), r["host"])
	assert.IsType(t, string(0), r["topology_id"])
	assert.IsType(t, string(0), r["node_id"])
	assert.Equal(t, "123", r["correlation_id"])
}
