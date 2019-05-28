package server

import (
	"net/http"

	"github.com/gin-gonic/gin"

	"topology-generator/pkg/config"
	"topology-generator/pkg/docker_client"
	"topology-generator/pkg/storage"
)

// New Create new http.Server
func New(mongo *storage.MongoDefault, docker *docker_client.DockerApiClient) *http.Server {
	return &http.Server{
		Addr:    config.API.Host,
		Handler: newMux(mongo, docker),
	}
}

type mux struct {
	*gin.Engine
	mongo  *storage.MongoDefault
	docker *docker_client.DockerApiClient
}

func newMux(mongo *storage.MongoDefault, docker *docker_client.DockerApiClient) http.Handler {
	r := gin.New()
	r.Use(gin.Logger())
	r.Use(gin.Recovery())
	m := mux{Engine: r, mongo: mongo, docker: docker}
	m.version1()

	return r
}

// V1 routes
func (m *mux) version1() {
	handler, err := GetHandlerAdapter(config.Generator.Mode, m.mongo, m.docker)
	if err != nil {
		panic(err)
	}

	v1 := m.Group("/v1", apiVersion("1", "0"))
	{
		v1.GET("/status", Wrap(func(c *contextWrapper) { c.OK("ok") }))
		v1.POST("/api/topologies/:topologyId", Wrap(handler.GenerateAction))
		v1.PUT("/api/topologies/:topologyId", Wrap(handler.RunStopAction))
		v1.DELETE("/api/topologies/:topologyId", Wrap(handler.DeleteAction))
		v1.GET("/api/topologies/:topologyId", Wrap(handler.InfoAction))
	}
}
