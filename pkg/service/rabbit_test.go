package service

import (
	"bytes"
	"io/ioutil"
	"net/http"
	"testing"

	"go.mongodb.org/mongo-driver/v2/bson"
	"starting-point/pkg/storage"
	"starting-point/pkg/utils"
)

func TestRabbit(t *testing.T) {
	t.Skip()
	ConnectToRabbit()

	reader := ioutil.NopCloser(bytes.NewBuffer([]byte{}))
	r := &http.Request{Body: reader, Header: map[string][]string{"contentType": {"aaa"}}}
	topology := storage.Topology{Name: "Topology", ID: bson.NewObjectID(), Node: &storage.Node{ID: bson.NewObjectID(), Name: "Node"}}

	RabbitMq.SendMessage(r, topology, utils.InitFields())
	RabbitMq.Disconnect()
}

func TestRunSource(t *testing.T) {
	original := isPulseKey
	isPulseKey = func(key string) bool { return key == "backend-key" }
	t.Cleanup(func() { isPulseKey = original })

	for name, test := range map[string]struct {
		source, key, want string
	}{
		"webhook":                {source: "", key: "", want: "auto"},
		"ui":                     {source: "ui", key: "", want: "ui"},
		"pulse from the backend": {source: "pulse", key: "backend-key", want: "pulse"},
		"forged pulse":           {source: "pulse", key: "", want: "auto"},
		"pulse with a wrong key": {source: "pulse", key: "guess", want: "auto"},
		"unknown source":         {source: "cron", key: "backend-key", want: "auto"},
	} {
		t.Run(name, func(t *testing.T) {
			r := &http.Request{Header: http.Header{}}
			r.Header.Set("source", test.source)
			r.Header.Set(utils.PulseKey, test.key)

			if got := runSource(r); got != test.want {
				t.Fatalf("got %q, want %q", got, test.want)
			}
		})
	}
}
