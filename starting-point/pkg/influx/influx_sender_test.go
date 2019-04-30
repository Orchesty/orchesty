package influx

import (
	"go.mongodb.org/mongo-driver/bson/primitive"
	"starting-point/pkg/storage"
	"starting-point/pkg/udp"
	"testing"
)

func TestSendMetrics(t *testing.T) {
	udp.ConnectToUDP()

	topology := storage.Topology{Name: "Topology", ID: primitive.NewObjectID(), Node: &storage.Node{ID: primitive.NewObjectID(), Name: "Node"}}
	tags := GetTags(topology, "123")
	fields := GetFields(InitFields())
	fields["escaped"] = "aaa/bbb"
	fields["empty"] = ""
	fields["int"] = 1
	fields["boolT"] = true
	fields["boolF"] = false
	fields["null"] = nil
	fields["func"] = func() {}

	SendMetrics(tags, fields)
}

func TestEmptyFields(t *testing.T) {
	udp.ConnectToUDP()

	SendMetrics(make(map[string]interface{}), make(map[string]interface{}))
}

func TestEmptyTags(t *testing.T) {
	udp.ConnectToUDP()

	fields := GetFields(InitFields())

	SendMetrics(make(map[string]interface{}), fields)
}
