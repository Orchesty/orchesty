package model

import (
	"fmt"
	"hanaboso/topologygenerator/log"

	"hanaboso/utils/topology"

	"github.com/spf13/viper"
	"gopkg.in/mgo.v2"
	"gopkg.in/mgo.v2/bson"
)

var (
	topologyC *mgo.Collection
	nodeC     *mgo.Collection
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

	topologyC = conn.session.DB(conn.database).C(conn.topologyCollection)
	nodeC = conn.session.DB(conn.database).C(conn.nodeCollection)
	return
}

func (m *MongoDb) connect(host string, port int) (err error) {
	url := fmt.Sprintf("mongodb://%s:%d", host, port)
	log.Infof("Mongodb server: %s", url)
	m.session, err = mgo.Dial(url)

	if err != nil {
		panic(AppError{Message: err.Error(), Type: MONGODB})
	} else {
		log.Infof("Connection established to mongodb server: %s", url)
	}

	m.session.SetMode(mgo.Monotonic, true)
	return
}

func (m *MongoDb) GetTopologyById(topologyId string) (*topology.Topology, error) {
	var top topology.Topology
	if err := topologyC.With(m.session.Clone()).FindId(bson.ObjectIdHex(topologyId)).One(&top); err != nil {
		return nil, err
	}
	return &top, nil
}

func (m *MongoDb) GetNodesByTopologyId(topologyId string) ([]topology.Node, error) {
	var nodes []topology.Node
	if err := nodeC.With(m.session.Clone()).Find(bson.M{"topology": topologyId}).All(&nodes); err != nil {
		return nil, err
	}
	return nodes, nil
}

func (m *MongoDb) Close() {
	if m.session != nil {
		m.session.Close()
	}
}
