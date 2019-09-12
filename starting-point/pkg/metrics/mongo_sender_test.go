package metrics

import (
	"go.mongodb.org/mongo-driver/bson/primitive"
	"starting-point/pkg/config"
	"starting-point/pkg/storage"
	"testing"
)

func TestMongo_SendMetrics(t *testing.T) {
	config.Config.MongoDB.Hostname = "127.0.0.44"
	storage.CreateMongo()

	topology := storage.Topology{Name: "Topology", ID: primitive.NewObjectID(), Node: &storage.Node{ID: primitive.NewObjectID(), Name: "Node"}}
	tags := GetTags(topology, "123")
	fields := GetFields(InitFields())
	fields["escaped"] = "aaa/bbb"
	fields["empty"] = ""
	fields["int"] = 1
	fields["boolT"] = true
	fields["boolF"] = false
	fields["null"] = nil

	m := newMongoSender()
	m.SendMetrics(tags, fields)
}
