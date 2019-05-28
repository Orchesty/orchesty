package server

import (
	"errors"
	"fmt"
	"net/http"

	"github.com/gin-gonic/gin"

	"topology-generator/pkg/config"
	"topology-generator/pkg/fs_commands"
	"topology-generator/pkg/model"
	"topology-generator/pkg/services"
)

func (m *DockerCompose) InfoAction(c *contextWrapper) {
	id := c.Param("topologyId")
	topology, err := loadTopologyData(id, m.mongo)

	if err != nil {
		c.NOK(err)
		return
	}

	containers, err := services.GetDockerTopologyInfo(m.docker, "running", topology.GetDockerName())

	if err != nil {
		c.NOK(err)
		return
	}

	//TODO: solve messaging
	var message = fmt.Sprintf("Topology ID: %s. Not found", id)
	if len(containers) > 0 {
		message = fmt.Sprintf("ID: %s", id)
		c.OK(gin.H{"message": message, "docker-info": containers})
	} else {
		c.WithCode(http.StatusNotFound, gin.H{"message": message, "docker-info": containers})
	}
}

func (m *DockerCompose) GenerateAction(c *contextWrapper) {
	generateAction(m.mongo, c, config.Generator)
}

func (m *DockerCompose) RunStopAction(c *contextWrapper) {
	var body body
	if err := c.ShouldBind(&body); err != nil {
		c.NOK(WrapBindErr(model.ErrRequestMalformed, err))
		return
	}

	id := c.Param("topologyId")
	topology, err := loadTopologyData(id, m.mongo)

	if err != nil {
		c.NOK(err)
		return
	}

	var (
		status  int
		message string
	)

	if topology.ID.Hex() != "" {
		if body.Action == "start" {
			dstDir := fmt.Sprintf("%s/%s", config.Generator.Path, topology.GetSaveDir())

			configPath := fmt.Sprintf("%s/docker-compose.yml", dstDir)
			err, _, stdErr := fs_commands.Execute("docker-compose", "-f", configPath, "up", "-d")

			if err != nil {
				status = http.StatusInternalServerError
				message = fmt.Sprintf("%s [%s]", err.Error(), stdErr.String())
			} else {
				status = http.StatusOK
				message = fmt.Sprintf("ID: %s", id)
			}

			containers, err := services.GetDockerTopologyInfo(m.docker, "running", topology.GetDockerName())
			c.WithCode(status, gin.H{"message": message, "docker-info": containers})
		} else if body.Action == "stop" {
			dstDir := fmt.Sprintf("%s/%s", config.Generator.Path, topology.GetSaveDir())
			configPath := fmt.Sprintf("%s/docker-compose.yml", dstDir)
			err, _, stdErr := fs_commands.Execute("docker-compose", "-f", configPath, "down")

			if err != nil {
				status = http.StatusInternalServerError
				message = fmt.Sprintf("%s [%s]", err.Error(), stdErr.String())
			} else {
				status = http.StatusOK
				message = fmt.Sprintf("ID: %s", id)
			}

			c.WithCode(status, gin.H{"message": message, "docker-info": nil})
		} else {
			c.NOK(errors.New(fmt.Sprintf("action %s not allow", body.Action)))
		}

	} else {
		message = fmt.Sprintf("Topology ID: %s. Not found", id)
		c.WithCode(status, gin.H{"message": message, "docker-info": nil})
	}
}

func (m *DockerCompose) DeleteAction(c *contextWrapper) {
	id := c.Param("topologyId")
	topology, err := loadTopologyData(id, m.mongo)

	if err != nil {
		c.NOK(err)
		return
	}

	var (
		status  int
		message string
	)

	dstDir := fmt.Sprintf("%s/%s", config.Generator.Path, topology.GetSaveDir())
	err = fs_commands.RemoveDirectory(dstDir)

	if err != nil {
		status = http.StatusInternalServerError
		message = err.Error()
	} else {
		status = http.StatusOK
		message = fmt.Sprintf("ID: %s", topology.ID.Hex())
	}

	c.WithCode(status, gin.H{"message": message, "docker-info": nil})
}
