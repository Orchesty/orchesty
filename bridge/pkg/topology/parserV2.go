package topology

import (
	"encoding/json"
	"fmt"
	"github.com/hanaboso/pipes/bridge/pkg/model"
	"github.com/hanaboso/pipes/bridge/pkg/utils/intx"
	"io/ioutil"
	"os"
	"strings"
	"time"
)

type jsonParserV2 struct{}

/*
{
   "id":"5cc0474e4e9acc00282bb942",
   "name":"test",
   "nodes":[
      {
         "id":"5cc047dd4e9acc002a200c12",
         "name":"start",
         "worker":"worker.null",
         "application": "losos",
         "settings":{
            "url":"http://:0",
            "actionPath":"",
            "method":"",
            "timeout":30,
            "userTask": "pending"
         },
         "followers":[
            {
               "id":"5cc047dd4e9acc002a200c14",
               "name":"Xml_parser"
            }
         ]
      }
   ],
   "rabbitMq":[
      {
         "dsn":"amqp://rabbitmq:20/%2F"
      }
   ]
}
*/

func (jsonParserV2) getTopology(path string) (model.Topology, error) {
	file, err := os.Open(path)
	if err != nil {
		return model.Topology{}, fmt.Errorf("topology json file path [%s] does not exist: %v", path, err)
	}
	defer file.Close()

	data, err := ioutil.ReadAll(file)
	if err != nil {
		return model.Topology{}, err
	}

	var topologyV2 model.TopologyV2
	if err := json.Unmarshal(data, &topologyV2); err != nil {
		return model.Topology{}, fmt.Errorf("topology json file path [%s] is not valid: %v", path, err)
	}

	topology := model.Topology{
		ID:      topologyV2.Id,
		Name:    topologyV2.Name,
		Nodes:   make([]model.Node, len(topologyV2.Nodes)),
		Shards:  make([]model.NodeShard, len(topologyV2.Nodes)),
		Timeout: 0,
	}

	maxTimeout := 0

	for i, nodeV2 := range topologyV2.Nodes {
		worker, err := workerTypeFromString(string(nodeV2.Worker))
		if err != nil {
			return model.Topology{}, err
		}
		maxTimeout = intx.Max(maxTimeout, nodeV2.Settings.Timeout)

		node := model.Node{
			ID:          nodeV2.Id,
			Name:        nodeV2.Name,
			Application: nodeV2.Application,
			Worker:      worker,
			Settings: model.NodeSettings{
				Url:        nodeV2.Settings.Url,
				ActionPath: strings.TrimPrefix(nodeV2.Settings.ActionPath, "/"),
				Headers:    nodeV2.Settings.Headers,
				Bridge: model.NodeSettingsBridge{
					Prefetch: nodeV2.Settings.RabbitPrefetch,
					Timeout:  nodeV2.Settings.Timeout,
				},
			},
			Followers: make([]model.Follower, len(nodeV2.Followers)),
			Messages:  make(chan *model.ProcessMessage, 0),
		}

		for j, f := range nodeV2.Followers {
			node.Followers[j] = model.Follower{
				Id:   f.Id,
				Name: f.Name,
			}
		}

		topology.Nodes[i] = node
		topology.Shards[i] = model.NodeShard{
			RabbitMQDSN: topologyV2.RabbitMq[0].Dsn,
			Index:       1,
			Node:        &node,
		}
	}

	topology.Timeout = time.Duration(maxTimeout)

	return topology, nil
}
