package compose

import (
	"hanaboso.com/topologygenerator/model"
)

type DockerCompose struct {
	model.UrlHandler
	Db *model.MongoDb
}
