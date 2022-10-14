package service

import (
	"bytes"
	"io/ioutil"
	"net/http"
	"testing"

	"go.mongodb.org/mongo-driver/bson/primitive"
	"starting-point/pkg/storage"
	"starting-point/pkg/utils"
)

func TestRabbit(t *testing.T) {
	t.Skip()
	ConnectToRabbit()

	reader := ioutil.NopCloser(bytes.NewBuffer([]byte{}))
	r := &http.Request{Body: reader, Header: map[string][]string{"contentType": {"aaa"}}}
	topology := storage.Topology{Name: "Topology", ID: primitive.NewObjectID(), Node: &storage.Node{ID: primitive.NewObjectID(), Name: "Node"}}

	RabbitMq.SendMessage(r, topology, utils.InitFields())
	RabbitMq.ClearChannels()
	RabbitMq.DisconnectRabbit()
}
