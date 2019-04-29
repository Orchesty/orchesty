package utils

import (
	"github.com/stretchr/testify/assert"
	"go.mongodb.org/mongo-driver/bson/primitive"
	"starting-point/pkg/storage"
	"testing"
)

func TestGenerateTplgName(t *testing.T) {
	topology := storage.Topology{Name: "Topology", ID: primitive.NewObjectID(), Node: &storage.Node{ID: primitive.NewObjectID(), Name: "Node"}}
	name := GenerateTplgName(topology)

	assert.Contains(t, name, "pipes.")
}
