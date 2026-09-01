package utils

import (
	"testing"

	"github.com/stretchr/testify/assert"
	"go.mongodb.org/mongo-driver/v2/bson"
	"starting-point/pkg/storage"
)

func TestGetTags(t *testing.T) {
	topology := storage.Topology{Name: "Topology", ID: bson.NewObjectID(), Node: &storage.Node{ID: bson.NewObjectID(), Name: "Node"}}
	r := GetTags(topology, "123")

	assert.IsType(t, string(rune(0)), r["host"])
	assert.IsType(t, string(rune(0)), r["topology_id"])
	assert.IsType(t, string(rune(0)), r["node_id"])
	assert.Equal(t, "123", r["correlation_id"])
}
