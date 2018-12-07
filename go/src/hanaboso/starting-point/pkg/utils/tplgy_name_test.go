package utils

import (
	"github.com/mongodb/mongo-go-driver/bson/objectid"
	"github.com/stretchr/testify/assert"
	"starting-point/pkg/storage"
	"testing"
)

func TestGenerateTplgName(t *testing.T) {
	topology := storage.Topology{Name: "Topology", ID: objectid.New(), Node: &storage.Node{ID: objectid.New(), Name: "Node"}}
	name := GenerateTplgName(topology)

	assert.Contains(t, name, "pipes.")
}
