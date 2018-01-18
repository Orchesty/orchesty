package model

import (
	"gopkg.in/mgo.v2"
	"fmt"
	"gopkg.in/mgo.v2/bson"
	"log"
	"hanaboso.com/pipescommon/topology"
	"github.com/spf13/viper"
)

type MongoDb struct {
	session            *mgo.Session
	database           string
	topologyCollection string
	nodeCollection     string
}

func CreateConnection(host string, port int) (conn *MongoDb) {
	conn = new(MongoDb)
	conn.database = viper.GetString("mongodb.database")
	conn.topologyCollection = viper.GetString("mongodb.topology-collection")
	conn.nodeCollection = viper.GetString("mongodb.node-collection")
	conn.connect(host, port)
	return
}

func (m *MongoDb) connect(host string, port int) (err error) {
	url := fmt.Sprintf("mongodb://%s:%d", host, port)
	log.Printf("Mongodb server: %s", url)
	m.session, err = mgo.Dial(url)

	if err != nil {
		panic(AppError{Message: err.Error(), Type: MONGODB})
	} else {
		log.Println("Connection established to mongodb server:", url)
	}

	m.session.SetMode(mgo.Monotonic, true)

	return
}

func (m *MongoDb) GetTopologyById(topologyId string) (topology.Topology, error) {
	var top topology.Topology
	t := m.session.DB(m.database).C(m.topologyCollection)
	err := t.FindId(bson.ObjectIdHex(topologyId)).One(&top)

	if err != nil {
		return topology.Topology{}, err
	} else {
		return top, err
	}

}

func (m *MongoDb) GetNodesByTopologyId(topologyId string) ([]topology.Node, error) {
	t := m.session.DB(m.database).C(m.nodeCollection)

	var nodes []topology.Node

	err := t.Find(bson.M{"topology": topologyId}).All(&nodes)

	if err != nil {
		return []topology.Node{}, err
	} else {
		return nodes, err
	}
}

func (m *MongoDb) Close() {
	if m.session != nil {
		m.session.Close()
	}
}
