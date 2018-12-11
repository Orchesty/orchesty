package utils

import (
	"github.com/mongodb/mongo-go-driver/bson/primitive"
	"github.com/stretchr/testify/assert"
	"starting-point/pkg/storage"
	"testing"
)

func TestGenerateTplgName(t *testing.T) {
	topology := storage.Topology{Name: "Topology", ID: primitive.NewObjectID(), Node: &storage.Node{ID: primitive.NewObjectID(), Name: "Node"}}
	name := GenerateTplgName(topology)

	assert.Contains(t, name, "pipes.")
}
