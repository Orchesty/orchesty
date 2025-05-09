package utils

import (
	"testing"

	"github.com/stretchr/testify/assert"
	"go.mongodb.org/mongo-driver/v2/bson"
	"starting-point/pkg/storage"
)

func TestGenerateTplgName(t *testing.T) {
	topology := storage.Topology{Name: "Topology", ID: bson.NewObjectID(), Node: &storage.Node{ID: bson.NewObjectID(), Name: "Node"}}
	name := GenerateTplgName(topology)

	assert.Contains(t, name, "pipes.")
}
