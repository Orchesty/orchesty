package topology

import (
	"fmt"
	"gopkg.in/mgo.v2/bson"
	"hanaboso/pipescommon/utils"
)

type Node struct {
	ID       bson.ObjectId `bson:"_id"`
	Name     string        `bson:"name"`
	Topology string        `bson:"topology"`
	Next []struct {
		ID   string `bson:"id"`
		Name string `bson:"name"`
	} `bson:"next"`
	Type    string `bson:"type"`
	Handler string `bson:"handler"`
	Enabled bool   `bson:"enabled"`
	Deleted bool   `bson:"deleted"`
}

func (n Node) GetServiceName() string {
	//TODO: add webalize to Name
	return fmt.Sprintf("%s-%s", n.ID.Hex(), n.Name)
}

func (n Node) GetNext() []string {

	var nexts []string
	nexts = make([]string, 0)

	for _, next := range n.Next {
		nexts = append(nexts, utils.CreateServiceName(fmt.Sprintf("%s-%s", next.ID, next.Name)))
	}

	return nexts
}
