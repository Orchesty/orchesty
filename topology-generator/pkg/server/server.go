package server

import (
	"github.com/gin-gonic/gin"
	"net/http"
	"topology-generator/pkg/config"
	"topology-generator/pkg/model"
	"topology-generator/pkg/services"
)

// New create new http.Server
func New(sc *services.ServiceContainer) *http.Server {
	return &http.Server{
		Addr:    config.API.Host,
		Handler: newMux(sc),
	}
}

type mux struct {
	*gin.Engine
	Sc *services.ServiceContainer
}

func newMux(sc *services.ServiceContainer) http.Handler {
	r := gin.New()
	r.Use(gin.Logger())
	r.Use(gin.Recovery())
	m := mux{Engine: r, Sc: sc}
	m.version1()

	return r
}

// V1 routes
func (m *mux) version1() {
	handler, err := GetHandlerAdapter(model.Adapter(config.Generator.Mode))
	if err != nil {
		panic(err)
	}

	v1 := m.Group("/v1", apiVersion("1", "0"))
	{
		v1.GET("/status", Wrap(func(c *ContextWrapper) { c.OK("ok") }, m.Sc))
		v1.POST("/api/topologies/:topologyId", Wrap(handler.GenerateAction, m.Sc))
		v1.GET("/api/topologies/:topologyId/host", Wrap(handler.HostAction, m.Sc))
		v1.PUT("/api/topologies/:topologyId", Wrap(handler.RunStopAction, m.Sc))
		v1.DELETE("/api/topologies/:topologyId", Wrap(handler.DeleteAction, m.Sc))
		v1.GET("/api/topologies/:topologyId", Wrap(handler.InfoAction, m.Sc))
	}
}
