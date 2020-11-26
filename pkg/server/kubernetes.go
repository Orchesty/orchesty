package server

import (
	"fmt"
	"github.com/gin-gonic/gin"
	"net/http"
	"strings"
	"topology-generator/pkg/config"
	"topology-generator/pkg/model"
	"topology-generator/pkg/services"
)

// GenerateAction GenerateAction
func (k *Kubernetes) GenerateAction(c *ContextWrapper) {
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
	err = c.Sc.Kubernetes.Generate(topologyService)
	if err != nil {
		c.WithCode(http.StatusInternalServerError, gin.H{"message": fmt.Sprintf("Generating Kuberneted deployment failed because: %v", err)})
		return
	}

	c.OK(gin.H{"message": fmt.Sprintf("ID: %s", id)})

}

// RunStopAction RunStopAction
func (k *Kubernetes) RunStopAction(c *ContextWrapper) {
	var body body
	if err := c.ShouldBind(&body); err != nil {
		c.NOK(WrapBindErr(model.ErrRequestMalformed, err))
		return
	}

	id := c.Param("topologyId")

	err := c.Sc.Kubernetes.RunStop(id, c.Sc.Mongo, body.Action)

	if err != nil {
		c.WithCode(http.StatusInternalServerError, gin.H{"message": fmt.Sprintf("Error trying to run/stop deployment fpr topology %s. Reason: %v", id, err)})
		return
	}
	c.WithCode(http.StatusOK, gin.H{"message": fmt.Sprintf("ID: %s", id)})
}

// DeleteAction DeleteAction
func (k *Kubernetes) DeleteAction(c *ContextWrapper) {
	id := c.Param("topologyId")

	err := c.Sc.Kubernetes.DeleteAll(id, c.Sc.Mongo, config.Generator)

	if err != nil {
		c.WithCode(http.StatusInternalServerError, gin.H{"message": fmt.Sprintf("Error deleting deployment. Reason: %v", err)})
		return
	}
	c.WithCode(http.StatusOK, gin.H{"message": fmt.Sprintf("ID: %s", id)})
}

// InfoAction InfoAction
func (k *Kubernetes) InfoAction(c *ContextWrapper) {
	id := c.Param("topologyId")

	containers, err := c.Sc.Kubernetes.Info(services.GetDeploymentName(id))
	if err != nil && !strings.Contains(err.Error(), "not found") {
		c.NOK(err)
		return
	}

	var message = fmt.Sprintf("Topology ID: %s. Not found", id)
	if err != nil && strings.Contains(err.Error(), "not found") {
		c.WithCode(http.StatusNotFound, gin.H{"": message, "docker-info": containers})
		return
	}
	message = fmt.Sprintf("ID: %s", id)
	c.OK(gin.H{"message": message, "docker-info": containers})
}
