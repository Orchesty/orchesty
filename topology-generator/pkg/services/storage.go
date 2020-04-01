package services

import (
	"topology-generator/pkg/model"
	"topology-generator/pkg/storage"
)

type mongodb struct {
	db storage.MongoInterface
}

// GetTopology GetTopology
func (m *mongodb) GetTopology(id string) (*model.Topology, error) {
	return m.db.FindTopologyByID(id)
}

// GetTopologyNodes GetTopologyNodes
func (m *mongodb) GetTopologyNodes(id string) ([]model.Node, error) {
	return m.db.FindNodesByTopology(id)
}

// StorageSvc StorageSvc
type StorageSvc interface {
	GetTopology(id string) (*model.Topology, error)
	GetTopologyNodes(id string) ([]model.Node, error)
}

// NewStorageSvc NewStorageSvc
func NewStorageSvc(db storage.MongoInterface) StorageSvc {
	return &mongodb{db: db}
}
