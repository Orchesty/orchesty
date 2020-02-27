package service

import (
	"bytes"
	"io/ioutil"
	"net/http"
	"os"
	"testing"

	"go.mongodb.org/mongo-driver/bson/primitive"
	"starting-point/pkg/storage"
	"starting-point/pkg/utils"
)

func TestRabbit(t *testing.T) {
	if os.Getenv("GITLAB_CI") == "true" {
		t.Skip()
	}

	ConnectToRabbit()

	reader := ioutil.NopCloser(bytes.NewBuffer([]byte{}))
	r := &http.Request{Body: reader, Header: map[string][]string{"contentType": {"aaa"}}}
	topology := storage.Topology{Name: "Topology", ID: primitive.NewObjectID(), Node: &storage.Node{ID: primitive.NewObjectID(), Name: "Node"}}

	RabbitMq.SndMessage(r, topology, utils.InitFields(), false, false)
	RabbitMq.ClearChannels()
	RabbitMq.DisconnectRabbit()
}
