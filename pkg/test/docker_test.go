//+build integration_test

package test

import (
	"bytes"
	"encoding/json"
	"fmt"
	"github.com/gin-gonic/gin"
	"github.com/hanaboso/go-log/pkg/zap"
	"github.com/stretchr/testify/require"
	"log"
	"net/http"
	"net/http/httptest"
	"os"
	"testing"
	"topology-generator/pkg/config"
	"topology-generator/pkg/model"
	"topology-generator/pkg/server"
	"topology-generator/pkg/services"
	"topology-generator/pkg/storage"
)

type TestEnv struct {
	t *testing.T
	//db      mongo.Client
	handler http.Handler
}

func startServer(sc *services.ServiceContainer) *http.Server {
	s := server.New(sc)
	logger := zap.NewLogger()

	go func() {
		logger.WithFields(map[string]interface{}{
			"address": s.Addr,
		}).Info("Starting API server...")
		// service connections
		if err := s.ListenAndServe(); err != http.ErrServerClosed {
			logger.WithFields(map[string]interface{}{
				"address": s.Addr,
			}).Fatal("API server start failed, reason:", err)
		}
	}()

	return s
}

func NewTestEnv(t *testing.T) *TestEnv {

	gin.SetMode(gin.TestMode)

	storageDb := storage.CreateMongo()
	docker, err := services.DockerConnect()
	if err != nil {
		t.Fatal("APi server shutdown, reason:", err)
	}

	sc := services.NewServiceContainer(storageDb, docker, nil, config.Generator)

	s := startServer(sc)
	return &TestEnv{
		t: t,
		//db:      storageDb,
		handler: s.Handler,
	}
}

func TestAll(t *testing.T) {
	env := NewTestEnv(t)
	// i need to make sure somehow i am not running in swarm mode
	_, _, _ = fscommands.Execute("docker", []string{"swarm", "leave", "--force"}...)
	testDocker(t, env, model.ModeCompose, true)
	testDocker(t, env, model.ModeCompose, false)

	// need to run in swarm mode
	_, _, _ = fscommands.Execute("docker", []string{"swarm", "init"}...)
	testDocker(t, env, model.ModeSwarm, true)
	testDocker(t, env, model.ModeSwarm, false)
}

func testDocker(t *testing.T, env *TestEnv, mode model.Adapter, multiNode bool) {
	nodeConfig := getTestNodeConfig()
	nodeConfig.Environment.GeneratorMode = mode
	config.Generator.MultiNode = multiNode
	count := 1
	if !multiNode {
		count = 2
	}
	defer env.Close()
	publish(t, env, nodeConfig)
	startStop(t, env, "start")
	info(t, env, count, http.StatusOK)
	startStop(t, env, "stop")
	info(t, env, 0, http.StatusNotFound)
	deleteTopology(t, env)
	info(t, env, 0, http.StatusNotFound)
}

func publish(t *testing.T, env *TestEnv, nodeConfig *model.NodeConfig) {
	requestBody, err := json.Marshal(nodeConfig)
	if err != nil {
		log.Fatal("failed marshaling generate request body: ", err.Error())
	}
	response := make(map[string]interface{})
	env.POST(fmt.Sprintf("http://127.0.0.1:8000/v1/api/topologies/%s", topologyID), bytes.NewBuffer(requestBody), func(r *httptest.ResponseRecorder, rq *http.Request) {
		err := json.Unmarshal(r.Body.Bytes(), &response)
		require.Nil(t, err, r.Body.String())
		message := response["message"].(string)
		require.Equal(t, fmt.Sprintf("ID: %s", topologyID), message)
		require.Equal(t, http.StatusOK, r.Code, r.Body.String())
		dstDir := services.GetDstDir(config.Generator.Path, fmt.Sprintf("%s-test", topologyID))
		require.DirExists(t, dstDir)
		require.FileExists(t, fmt.Sprintf("%s/docker-compose.yml", dstDir))
		require.FileExists(t, fmt.Sprintf("%s/topology.json", dstDir))
	})
}

func startStop(t *testing.T, env *TestEnv, action string) {
	data := make(map[string]string)
	data["Action"] = action
	requestBody, err := json.Marshal(data)
	if err != nil {
		log.Fatal("failed marshaling generate request body: ", err.Error())
	}
	env.PUT(fmt.Sprintf("http://127.0.0.1:8000/v1/api/topologies/%s", topologyID), bytes.NewBuffer(requestBody), func(r *httptest.ResponseRecorder, rq *http.Request) {
		require.Equal(t, http.StatusOK, r.Code, r.Body.String())
	})
}

func info(t *testing.T, env *TestEnv, count int, status int) {
	response := make(map[string]interface{})
	env.GET(fmt.Sprintf("http://127.0.0.1:8000/v1/api/topologies/%s", topologyID), func(r *httptest.ResponseRecorder, rq *http.Request) {
		err := json.Unmarshal(r.Body.Bytes(), &response)
		require.Nil(t, err, r.Body.String())
		dockerInfo := response["docker-info"].([]interface{})
		require.Equal(t, count, len(dockerInfo), "Len of running containers must be", count)
		require.Equal(t, status, r.Code, r.Body.String())
	})
}

func deleteTopology(t *testing.T, env *TestEnv) {
	env.DELETE(fmt.Sprintf("http://127.0.0.1:8000/v1/api/topologies/%s", topologyID), func(r *httptest.ResponseRecorder, rq *http.Request) {
		require.Equal(t, http.StatusOK, r.Code, r.Body.String())
		// check whether following paths are really deleted
		dstDir := services.GetDstDir(config.Generator.Path, fmt.Sprintf("%s-test", topologyID))
		files := []string{
			dstDir,
			fmt.Sprintf("%s/docker-compose.yml", dstDir),
			fmt.Sprintf("%s/topology.json", dstDir),
		}

		for _, file := range files {
			if _, err := os.Stat(file); !os.IsNotExist(err) {
				t.FailNow()
			}
		}
	})
}
