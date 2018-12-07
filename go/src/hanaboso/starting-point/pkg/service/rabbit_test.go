package service

import (
	"bytes"
	"github.com/mongodb/mongo-go-driver/bson/objectid"
	"io/ioutil"
	"net/http"
	"starting-point/pkg/influx"
	"starting-point/pkg/storage"
	"starting-point/pkg/udp"
	"testing"
)

func TestRabbit(t *testing.T) {
	ConnectToRabbit()
	udp.ConnectToUDP()

	reader := ioutil.NopCloser(bytes.NewBuffer([]byte{}))
	r := &http.Request{Body: reader, Header: map[string][]string{"contentType": {"aaa"}}}
	topology := storage.Topology{Name: "Topology", ID: objectid.New(), Node: &storage.Node{ID: objectid.New(), Name: "Node"}}
	init := influx.InitFields()

	RabbitMq.SndMessage(r, topology, init, false, false)
	RabbitMq.ClearChannels()
	RabbitMq.DisconnectRabbit()
}
