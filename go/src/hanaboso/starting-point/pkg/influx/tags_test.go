package influx

import (
	"github.com/mongodb/mongo-go-driver/bson/objectid"
	"github.com/stretchr/testify/assert"
	"starting-point/pkg/storage"
	"testing"
)

func TestGetTags(t *testing.T) {
	topology := storage.Topology{Name: "Topology", ID: objectid.New(), Node: &storage.Node{ID: objectid.New(), Name: "Node"}}
	r := GetTags(topology, "123")

	assert.IsType(t, string(0), r["host"])
	assert.IsType(t, string(0), r["topology_id"])
	assert.IsType(t, string(0), r["node_id"])
	assert.Equal(t, "123", r["correlation_id"])
}
