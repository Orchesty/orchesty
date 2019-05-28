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

func (m *Swarm) InfoAction(c *contextWrapper) {
	id := c.Param("topologyId")

	topology, err := loadTopologyData(id, m.mongo)

	if err != nil {
		c.NOK(err)
		return
	}

	containers, err := services.GetSwarmTopologyInfo(m.docker, "running", topology.GetSwarmName(config.Generator.Prefix))

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

func (m *Swarm) GenerateAction(c *contextWrapper) {
	generateAction(m.mongo, c, config.Generator)
}

func (m *Swarm) RunStopAction(c *contextWrapper) {

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

	var status int
	var message string

	status = http.StatusInternalServerError

	if topology.ID.Hex() != "" {
		if body.Action == "start" {
			cmd, args := getSwarmCreateConfigCmd(topology, config.Generator)
			err, _, stdErr := fs_commands.Execute(cmd, args...)

			message = fmt.Sprintf("%s [%s]", err, stdErr.String())

			if err == nil {
				cmd, args = getSwarmRunCmd(topology)
				err, _, stdErr := fs_commands.Execute(cmd, args...)

				if err != nil {
					status = http.StatusInternalServerError
					message = fmt.Sprintf("%s [%s]", err, stdErr.String())
				} else {
					status = http.StatusOK
					message = fmt.Sprintf("ID: %s", id)
				}
			}

			containers, err := services.GetDockerTopologyInfo(m.docker, "running", topology.GetDockerName())
			c.WithCode(status, gin.H{"message": message, "docker-info": containers})
		} else if body.Action == "stop" {

			cmd, args := getSwarmStopCmd(topology, config.Generator.Prefix)
			err, _, stdErr := fs_commands.Execute(cmd, args...)

			if err == nil {
				cmd, args := getSwarmRmConfigCmd(topology, config.Generator.Prefix)
				err, _, stdErr = fs_commands.Execute(cmd, args...)
			}

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

func (m *Swarm) DeleteAction(c *contextWrapper) {
	id := c.Param("topologyId")

	topology, err := loadTopologyData(id, m.mongo)

	if err != nil {
		c.NOK(err)
		return
	}

	var status int
	var message string

	if topology.ID.Hex() != "" {
		cmd, args := getSwarmStopCmd(topology, config.Generator.Prefix)
		err, _, stdErr := fs_commands.Execute(cmd, args...)

		if err != nil {
			status = http.StatusInternalServerError
			message = fmt.Sprintf("%s [%s]", err.Error(), stdErr.String())
		} else {
			dstDir := fmt.Sprintf("%s/%s", config.Generator.Path, topology.GetSaveDir())
			err = fs_commands.RemoveDirectory(dstDir)

			status = http.StatusOK
			message = fmt.Sprintf("ID: %s", id)
		}

		c.WithCode(status, gin.H{"message": message, "docker-info": nil})
	} else {
		message = fmt.Sprintf("Topology ID: %s. Not found", id)
		c.WithCode(status, gin.H{"message": message, "docker-info": nil})
	}
}
