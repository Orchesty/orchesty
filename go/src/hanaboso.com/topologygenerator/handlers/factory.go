package handlers

import (
	"hanaboso.com/topologygenerator/model"
	"hanaboso.com/topologygenerator/handlers/compose"
	"github.com/spf13/viper"
	"hanaboso.com/topologygenerator/handlers/swarm"
	"hanaboso.com/topologygenerator/generator"
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
