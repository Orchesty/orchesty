package compose

import (
	"hanaboso/topologygenerator/model"
)

type DockerCompose struct {
	model.URLHandler
	Db *model.MongoDb
}
