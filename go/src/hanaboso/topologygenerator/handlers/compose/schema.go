package compose

import (
	"hanaboso/topologygenerator/model"
)

type DockerCompose struct {
	model.UrlHandler
	Db *model.MongoDb
}
