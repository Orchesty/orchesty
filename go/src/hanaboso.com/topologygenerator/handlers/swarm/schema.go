package swarm

import "hanaboso.com/topologygenerator/model"

type Swarm struct {
	model.UrlHandler
	Db *model.MongoDb
}
