package handlers

import (
	"hanaboso/topologygenerator/generator"
	"hanaboso/topologygenerator/handlers/compose"
	"hanaboso/topologygenerator/handlers/swarm"
	"hanaboso/topologygenerator/model"

	"github.com/spf13/viper"
)

func CreateHandler(mode string) model.UrlHandler {
	var handler model.UrlHandler

	switch mode {
	case generator.MODECOMPOSE:
		handler = &compose.DockerCompose{
			Db: model.CreateConnection(viper.GetString("mongodb.host"), viper.GetInt("mongodb.port")),
		}

	case generator.MODESWARM:
		handler = &swarm.Swarm{
			Db: model.CreateConnection(viper.GetString("mongodb.host"), viper.GetInt("mongodb.port")),
		}
	default:
		handler = nil
	}

	return handler
}
