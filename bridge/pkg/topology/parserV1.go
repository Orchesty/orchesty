package topology

import (
	"encoding/json"
	"fmt"
	"github.com/hanaboso/pipes/bridge/pkg/utils/intx"
	"io/ioutil"
	"os"
	"strings"
	"time"

	"github.com/hanaboso/pipes/bridge/pkg/enum"
	"github.com/hanaboso/pipes/bridge/pkg/model"
)

type jsonParserV1 struct{}

/* Currently supported .json format
{
    "id":"6050701e8e5eed55e6428702-run-pro-syn",
    "topology_id":"6050701e8e5eed55e6428702",
    "topology_name":"run-product-sync",
    "nodes":[
        {
            "id":"60507029a0099610951e6202-cro",
            "label": {
                "id":"60507029a0099610951e6202-cro",
                "node_id":"60507029a0099610951e6202",
                "node_name":"Cron"
            },
            "faucet": {

            },
            "worker": {
                "type":"worker.null",
                "settings":{
                    "publish_queue":{

                    }
                }
            },
            "next":[
                "60507029a0099610951e6203-get-sho-use"
            ],
            "debug":{
                "port":8008,
                "host":"mb-6050701e8e5eed55e6428702",
                "url":"http://mb-6050701e8e5eed55e6428702:8008/status"
            }
        }
    ]
}
*/

// getTopology returns topology model with enabled shard nodes
func (jsonParserV1) getTopology(path string) (model.Topology, error) {
	file, err := os.Open(path)
	if err != nil {
		return model.Topology{}, fmt.Errorf("topology json file path [%s] does not exist: %v", path, err)
	}
	defer file.Close()

	data, err := ioutil.ReadAll(file)
	if err != nil {
		return model.Topology{}, err
	}

	var topologyV1 model.TopologyV1
	if err := json.Unmarshal(data, &topologyV1); err != nil {
		return model.Topology{}, fmt.Errorf("topology json file path [%s] is not valid: %v", path, err)
	}

	topology := model.Topology{
		ID:      topologyV1.TopologyId,
		Name:    topologyV1.TopologyName,
		Nodes:   make([]model.Node, len(topologyV1.Nodes)),      // TODO 1:1 node:queue
		Shards:  make([]model.NodeShard, len(topologyV1.Nodes)), // TODO 1:1 node:queue
		Timeout: 60 * time.Second,                               // TODO Zjistit nejlepsi hodnotu
	}

	// id -> name
	followerList := make(map[string]string)

	for i, nodeV1 := range topologyV1.Nodes {
		followerList[nodeV1.Label.NodeID] = nodeV1.Label.NodeName
		workerType, err := workerTypeFromString(nodeV1.Worker.Type)
		if err != nil {
			return model.Topology{}, err
		}

		followers := make([]model.Follower, 0)
		for _, follower := range nodeV1.Next {
			followerId := follower[:len(follower)-4]
			followers = append(followers, model.Follower{
				Id:   followerId,
				Name: "",
			})
		}

		node := model.Node{
			ID:        nodeV1.Label.NodeID,
			Name:      nodeV1.Label.NodeName,
			Worker:    workerType,
			Followers: followers,
			Settings: model.NodeSettings{
				Url: fmt.Sprintf("http://%s:%d",
					nodeV1.Worker.Settings.Host,
					intx.Default(nodeV1.Worker.Settings.Port, 80),
				),
				ActionPath: strings.TrimPrefix(nodeV1.Worker.Settings.ProcessPath, "/"),
			},
		}

		// Currently each node is enabled and has a single shard
		topology.Nodes[i] = node
		topology.Shards[i] = model.NodeShard{
			RabbitMQDSN: "amqp://rabbitmq",
			Index:       1,
			Node:        &node,
		}
	}

	for i, node := range topology.Nodes {
		for j, follower := range node.Followers {
			topology.Nodes[i].Followers[j].Name = followerList[follower.Id]
		}
	}

	return topology, nil
}

// TODO 1. zkontrolovat hodnoty dle topologyGenerator, 2. potrebujeme to vubec?
func workerTypeFromString(workerType string) (enum.WorkerType, error) {
	switch workerType {
	case "worker.null":
		return enum.WorkerType_Null, nil
	case "worker.http", "worker.http_limited":
		return enum.WorkerType_Http, nil
	case "worker.batch", "splitter.amqprpc_limited", "splitter.amqprpc":
		return enum.WorkerType_Batch, nil
	case "worker.user", "worker.userTask":
		return enum.WorkerType_UserTask, nil
	case "worker.custom_node":
		return enum.WorkerType_Custom, nil
	}

	return enum.WorkerType_Null, fmt.Errorf("unknown worker type [%s]", workerType)
}
