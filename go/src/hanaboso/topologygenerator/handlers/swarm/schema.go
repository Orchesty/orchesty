package swarm

import "hanaboso/topologygenerator/model"

type Swarm struct {
	model.URLHandler
	Db *model.MongoDb
}
