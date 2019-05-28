package server

import (
	"fmt"
	"net/http"

	"github.com/gin-gonic/gin"
	log "github.com/sirupsen/logrus"

	"topology-generator/pkg/config"
	"topology-generator/pkg/fs_commands"
	"topology-generator/pkg/model"
	"topology-generator/pkg/services"
	"topology-generator/pkg/storage"
)

func generateAction(mongo *storage.MongoDefault, c *contextWrapper, configGenerator config.GeneratorConfig) {
	id := c.Param("topologyId")
	topology, err := loadTopologyData(id, mongo)

	if err != nil {
		c.NOK(err)
		return
	}

	nodes, err := mongo.FindNodesByTopology(id)

	if err != nil {
		c.NOK(err)
		return
	}

	var nodeConfig model.NodeConfig

	if err := c.ShouldBind(&nodeConfig); err != nil {
		c.NOK(WrapBindErr(model.ErrRequestMalformed, err))
	}

	topologyService := services.NewTopologyService(topology, nodes, nodeConfig, configGenerator)

	topologyJsonData, err := topologyService.CreateTopologyJson()
	if err != nil {
		c.NOK(err)
		return
	}

	dstFile := fmt.Sprintf("%s/%s", configGenerator.Path, topology.GetSaveDir())
	if err := fs_commands.WriteFile(
		dstFile,
		"topology.json",
		topologyJsonData,
	); err != nil {
		c.WithCode(http.StatusInternalServerError, gin.H{"message": fmt.Sprintf("Writing topology[id=%s, file=topology.json] failed. Reason: %v", id, err)})
		return
	}

	log.Debugf("Save topology.json to %s", dstFile)

	dockerCompose, err := topologyService.CreateDockerCompose(configGenerator.Mode)
	if err != nil {
		c.WithCode(http.StatusInternalServerError, gin.H{"message": fmt.Sprintf("Writing docker-compose[topology_id=%s] failed. Reason: %v", id, err)})
		return
	}

	if err := fs_commands.WriteFile(
		dstFile,
		"docker-compose.yml",
		dockerCompose,
	); err != nil {
		c.WithCode(http.StatusInternalServerError, gin.H{"message": fmt.Sprintf("Writing topology[id=%s, file=docker-compose.json] failed. Reason: %v", id, err)})
		return
	}

	log.Debugf("Save docker-compose.yml to %s", dstFile)

	c.OK(gin.H{"message": fmt.Sprintf("ID: %s", id)})
}

func getSwarmCreateConfigCmd(topology *model.Topology, generatorConfig config.GeneratorConfig) (string, []string) {
	topologyJson := fmt.Sprintf("%s/%s/topology.json", generatorConfig.Path, topology.GetSaveDir())
	return "docker", []string{
		"config",
		"create",
		topology.GetConfigName(generatorConfig.Prefix),
		topologyJson,
	}
}

func getSwarmRunCmd(topology *model.Topology) (string, []string) {
	dstDir := fmt.Sprintf("%s/%s", config.Generator.Path, topology.GetSaveDir())
	configPath := fmt.Sprintf("%s/docker-compose.yml", dstDir)

	return "docker", []string{
		"stack",
		"deploy",
		"--with-registry-auth",
		"-c",
		configPath,
		topology.GetTopologyPrefix(config.Generator.Prefix),
	}
}

func getSwarmStopCmd(topology *model.Topology, prefix string) (string, []string) {
	return "docker", []string{"stack", "rm", topology.GetTopologyPrefix(prefix)}
}

func getSwarmRmConfigCmd(topology *model.Topology, prefix string) (string, []string) {
	return "docker", []string{"config", "rm", topology.GetConfigName(prefix)}
}
