package swarm

import "hanaboso/topologygenerator/model"

type Swarm struct {
	model.UrlHandler
	Db *model.MongoDb
}
