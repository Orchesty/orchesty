package topology

import (
	"gopkg.in/mgo.v2/bson"
	"fmt"
	"hanaboso/pipescommon/utils"
)

type Topology struct {
	ID         bson.ObjectId `bson:"_id"`
	Name       string        `bson:"name"`
	Version    int           `bson:"version"`
	Descr      string        `bson:"descr"`
	Visibility string        `bson:"visibility"`
	Status     string        `bson:"status"`
	Enabled    bool          `bson:"enabled"`
	Bpmn       string        `bson:"bpmn"`
	RawBpmn    string        `bson:"rawBpmn"`
	Deleted    bool          `bson:"deleted"`
}

func (t *Topology) NormalizeName() string {
	//TODO: add webalize to Name
	return fmt.Sprintf("%s-%s", t.ID.Hex(), t.Name)
}

func (t *Topology) GetDockerName() string {
	//TODO: add dockerize
	return fmt.Sprintf("%s%s", t.ID.Hex(), t.Name)
}

func (t *Topology) GetMultiNodeName() string {
	return fmt.Sprintf("%s_mb", t.ID.Hex())
}

func (t *Topology) GetMultiServiceName() string {
	return fmt.Sprintf("%s_mb", t.ID.Hex())
}

func (t *Topology) GetSaveDir() string {
	return fmt.Sprintf("%s-%s", t.ID.Hex(), t.Name)
}

func (t *Topology) GetSwarmName(prefix string) string {
	return fmt.Sprintf("%s_%s", prefix, utils.Substring(t.ID.Hex(), 8, len(t.ID.Hex())))
}

func (t *Topology) GetProbeServiceName() string {
	return fmt.Sprintf("%s_probe", t.ID.Hex())
}

func (t *Topology) GetCounterServiceName() string {
	return fmt.Sprintf("%s_counter", t.ID.Hex())
}
