package service

import (
	"bytes"
	"go.mongodb.org/mongo-driver/bson/primitive"
	"io/ioutil"
	"net/http"
	"starting-point/pkg/metrics"
	"starting-point/pkg/storage"
	"starting-point/pkg/udp"
	"testing"
)

func TestRabbit(t *testing.T) {
	ConnectToRabbit()
	udp.ConnectToUDP()

	reader := ioutil.NopCloser(bytes.NewBuffer([]byte{}))
	r := &http.Request{Body: reader, Header: map[string][]string{"contentType": {"aaa"}}}
	topology := storage.Topology{Name: "Topology", ID: primitive.NewObjectID(), Node: &storage.Node{ID: primitive.NewObjectID(), Name: "Node"}}
	init := metrics.InitFields()

	RabbitMq.SndMessage(r, topology, init, false, false)
	RabbitMq.ClearChannels()
	RabbitMq.DisconnectRabbit()
}
