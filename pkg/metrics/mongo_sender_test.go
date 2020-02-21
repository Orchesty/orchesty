package metrics

import (
	"testing"

	"go.mongodb.org/mongo-driver/bson/primitive"
	"starting-point/pkg/storage"
)

func TestMongo_SendMetrics(t *testing.T) {
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
