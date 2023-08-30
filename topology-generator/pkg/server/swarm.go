package server

import (
	"fmt"
	"net/http"
	"topology-generator/pkg/services"

	"github.com/gin-gonic/gin"

	"topology-generator/pkg/config"
	"topology-generator/pkg/model"
)

// InfoAction InfoAction
func (m *Swarm) InfoAction(c *ContextWrapper) {
	id := c.Param("topologyId")

	topology, err := c.Sc.Mongo.GetTopology(id)

	if err != nil {
		c.NOK(err)
		return
	}

	containers, err := c.Sc.DockerCli.GetSwarmTopologyInfo("running", topology.GetSwarmName(config.Generator.Prefix))
	if err != nil {
		c.NOK(err)
		return
	}

	//TODO: solve messaging
	var message = fmt.Sprintf("Topology ID: %s. Not found", id)

	if len(containers) == 0 {
		c.WithCode(http.StatusNotFound, gin.H{"message": message, "docker-info": containers})
		return
	}
	message = fmt.Sprintf("ID: %s", id)
	c.OK(gin.H{"message": message, "docker-info": containers})
}

// GenerateAction GenerateAction
func (m *Swarm) GenerateAction(c *ContextWrapper) {
	id := c.Param("topologyId")

	var nodeConfig model.NodeConfig

	if err := c.ShouldBind(&nodeConfig); err != nil {
		c.NOK(WrapBindErr(model.ErrRequestMalformed, err))
		return
	}
	topologyService, err := services.NewTopologyService(nodeConfig, config.Generator, c.Sc.Mongo, id)
	if err != nil {
		c.NOK(err)
		return
	}

	err = c.Sc.Docker.Generate(topologyService)
	if err != nil {
		c.WithCode(http.StatusInternalServerError, gin.H{"message": fmt.Sprintf("error generating topology: %v", err)})
		return
	}

	c.OK(gin.H{"message": fmt.Sprintf("ID: %s", id)})
}

// RunStopAction RunStopAction
func (m *Swarm) RunStopAction(c *ContextWrapper) {
	var body body
	if err := c.ShouldBind(&body); err != nil {
		c.NOK(WrapBindErr(model.ErrRequestMalformed, err))
		return
	}

	id := c.Param("topologyId")

	containers, err := c.Sc.Docker.RunStopSwarm(id, c.Sc.Mongo, c.Sc.DockerCli, config.Generator, body.Action)
	if err != nil {
		c.WithCode(http.StatusInternalServerError, gin.H{"message": fmt.Sprintf("error runstopinng topology %s. Reason: %v", id, err)})
		return
	}
	c.WithCode(http.StatusOK, gin.H{"message": fmt.Sprintf("ID: %s", id), "docker-info": containers})
}

// DeleteAction DeleteAction
func (m *Swarm) DeleteAction(c *ContextWrapper) {
	id := c.Param("topologyId")

	err := c.Sc.Docker.DeleteSwarm(id, c.Sc.Mongo, c.Sc.DockerCli, config.Generator)

	if err != nil {
		c.WithCode(http.StatusInternalServerError, gin.H{"message": fmt.Sprintf("error deleting topology %s. Reason: %v", id, err)})
		return
	}
	c.WithCode(http.StatusOK, gin.H{"message": fmt.Sprintf("ID: %s", id), "docker-info": nil})

}
