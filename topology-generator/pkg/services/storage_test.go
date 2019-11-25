package services

import (
	"github.com/stretchr/testify/require"
	"testing"
	"topology-generator/pkg/model"
)

type mockStorageSvc struct{}

func (n mockStorageSvc) Connect() {
	return
}

func (n mockStorageSvc) Disconnect() error {
	return nil
}

func (n mockStorageSvc) FindTopologyByID(id string) (*model.Topology, error) {
	return getMockTopology(), nil
}

func (n mockStorageSvc) FindNodesByTopology(id string) ([]model.Node, error) {
	return getTestNodes(), nil
}

var mockDb StorageSvc

func setupStorageTest() {
	mockDb = NewStorageSvc(mockStorageSvc{})
}

func TestMongodb_GetTopology(t *testing.T) {
	setupStorageTest()
	topology, err := mockDb.GetTopology("test")
	if err != nil {
		t.Fatal(err)
	}
	require.NotNil(t, topology)
}

func TestMongodb_GetTopologyNodes(t *testing.T) {
	setupStorageTest()
	nodes, err := mockDb.GetTopologyNodes("test")
	if err != nil {
		t.Fatal(err)
	}
	require.Equal(t, 3, len(nodes))
}
