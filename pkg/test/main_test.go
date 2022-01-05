//+build integration_test

package test

import (
	"bytes"
	"context"
	"github.com/hanaboso/go-mongodb"
	"go.mongodb.org/mongo-driver/bson/primitive"
	"log"
	"net/http"
	"net/http/httptest"
	"os"
	"testing"
	"topology-generator/pkg/config"
	"topology-generator/pkg/model"
)

var client *mongo.Client

const topologyID = "5ddba690ffa5d5261c2d3fe2"

func TestMain(m *testing.M) {
	mongo := &mongodb.Connection{}
	mongo.Connect(config.Mongo.Dsn)

	insertTestData(mongo.Database)
	// Don't defer. os.Exit do not call defer.
	os.Exit(m.Run())
}

func insertTestData(db *mongo.Database) {
	coll := db.Collection(config.Mongo.Topology)
	objectId, err := primitive.ObjectIDFromHex(topologyID)
	if err != nil {
		log.Fatal("Creating primitive object id failed: ", err.Error())
	}
	topology := model.Topology{
		ID:         objectId,
		Name:       "test",
		Version:    1,
		Descr:      "",
		Visibility: "draft",
		Status:     "new",
		Enabled:    false,
		Bpmn:       "",
		RawBpmn:    "",
		Deleted:    false,
	}

	_, err = coll.InsertOne(context.Background(), topology)
	if err != nil {
		log.Fatal("Failed inserting topology: ", err.Error())
	}

	coll = db.Collection(config.Mongo.Node)

	nodeObjectId, err := primitive.ObjectIDFromHex("5ddba6a33ba8ab2922002a92")
	if err != nil {
		log.Fatal("Creating primitive object id failed: ", err.Error())
	}

	node := model.Node{
		ID:       nodeObjectId,
		Name:     "Start",
		Topology: topologyID,
		Next: []model.NodeNext{
			{
				ID:   "5ddba6a33ba8ab2922002a93",
				Name: "idnes",
			},
		},
		Type:    "start",
		Handler: "event",
		Enabled: true,
		Deleted: false,
	}

	_, err = coll.InsertOne(context.Background(), node)

	nodeObjectId, err = primitive.ObjectIDFromHex("5ddba6a33ba8ab2922002a93")
	if err != nil {
		log.Fatal("Creating primitive object id failed: ", err.Error())
	}

	node = model.Node{
		ID:       nodeObjectId,
		Name:     "idnes",
		Topology: topologyID,
		Next:     nil,
		Type:     "custom",
		Handler:  "action",
		Enabled:  true,
		Deleted:  false,
	}

	_, err = coll.InsertOne(context.Background(), node)
}

func getTestNodeConfig() *model.NodeConfig {
	return &model.NodeConfig{
		NodeConfig: map[string]model.NodeUserParams{
			"5ddba6a33ba8ab2922002a92": {
				Faucet: model.TopologyBridgeFaucetSettingsJSON{},
				Worker: model.TopologyBridgeWorkerJSON{
					Type: "worker.null",
					Settings: model.TopologyBridgeWorkerSettingsJSON{
						Host:        "",
						ProcessPath: "",
						StatusPath:  "",
						Method:      "",
						Port:        0,
						Secure:      false,
						Opts:        nil,
						PublishQueue: model.TopologyBridgeWorkerSettingsQueueJSON{
							Name:    "",
							Options: "",
						},
						ParserSettings: nil,
					},
				},
			},
			"5ddba6a33ba8ab2922002a93": {
				Faucet: model.TopologyBridgeFaucetSettingsJSON{},
				Worker: model.TopologyBridgeWorkerJSON{
					Type: "worker.http",
					Settings: model.TopologyBridgeWorkerSettingsJSON{
						Host:        "monolith-api",
						ProcessPath: "/custom_node/idnes/process",
						StatusPath:  "/custom_node/idnes/process/test",
						Method:      "POST",
						Port:        80,
						Secure:      false,
						Opts:        nil,
						PublishQueue: model.TopologyBridgeWorkerSettingsQueueJSON{
							Name:    "",
							Options: "",
						},
						ParserSettings: nil,
					},
				},
			},
		},
		Environment: model.Environment{
			DockerRegistry:      "dkr.hanaboso.net/pipes/pipes",
			DockerPfBridgeImage: "hanaboso/bridge:dev",
			RabbitMqDsn:         "rabbitmq:1000",
			MetricsHost:         "kapacitor",
			MetricsPort:         "9100",
			MetricsService:      "influx",
			WorkerDefaultPort:   8008,
			GeneratorMode:       "compose",
		},
	}
}

func (c *TestEnv) POST(path string, buffer *bytes.Buffer, f func(r *httptest.ResponseRecorder, rq *http.Request)) {

	req := httptest.NewRequest(http.MethodPost, path, buffer)
	req.Header.Set("Content-Type", "application/json")
	w := httptest.NewRecorder()
	c.handler.ServeHTTP(w, req)
	f(w, req)
}

func (c *TestEnv) PUT(path string, buffer *bytes.Buffer, f func(r *httptest.ResponseRecorder, rq *http.Request)) {

	req := httptest.NewRequest(http.MethodPut, path, buffer)
	req.Header.Set("Content-Type", "application/json")
	w := httptest.NewRecorder()
	c.handler.ServeHTTP(w, req)
	f(w, req)
}

func (c *TestEnv) GET(path string, f func(r *httptest.ResponseRecorder, rq *http.Request)) {

	req := httptest.NewRequest(http.MethodGet, path, &bytes.Buffer{})
	req.Header.Set("Content-Type", "application/json")
	w := httptest.NewRecorder()
	c.handler.ServeHTTP(w, req)
	f(w, req)
}

func (c *TestEnv) DELETE(path string, f func(r *httptest.ResponseRecorder, rq *http.Request)) {

	req := httptest.NewRequest(http.MethodDelete, path, &bytes.Buffer{})
	req.Header.Set("Content-Type", "application/json")
	w := httptest.NewRecorder()
	c.handler.ServeHTTP(w, req)
	f(w, req)
}

func (c *TestEnv) Close() {
	/*	if err := c.db.Close(); err != nil {
		c.t.Fatal(err)
	}*/
}
