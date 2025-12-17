package mongo

import (
	"context"
	"github.com/hanaboso/pipes/counter/pkg/config"
	"github.com/hanaboso/pipes/counter/pkg/model"
	"go.mongodb.org/mongo-driver/v2/bson"
	"time"
)

func (m *MongoDb) GetTopology(id string) (model.Topology, error) {
	ctx, cancel := context.WithTimeout(context.Background(), 30*time.Second)
	result := m.connection.Database.Collection(config.MongoDb.TopologyCollection).FindOne(ctx, bson.M{
		"_id": getId(id),
	})
	err := result.Err()
	if err != nil {
		println(err.Error())
		cancel()
		return model.Topology{}, err
	}

	var topology model.Topology
	err = result.Decode(&topology)

	cancel()
	return topology, err
}
