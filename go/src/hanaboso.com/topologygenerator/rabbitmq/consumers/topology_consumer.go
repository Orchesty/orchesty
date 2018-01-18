package consumers

import (
	"github.com/streadway/amqp"
	"log"
	"encoding/json"
	"hanaboso.com/topologygenerator/model"
	"hanaboso.com/topologygenerator/managers/compose"
	"github.com/spf13/viper"
	"hanaboso.com/topologygenerator/managers/swarm"
	"fmt"
)

type TopologyConsumer struct {
	model.CallbackFunction
	Db *model.MongoDb
}

type TopologyHandleMessage struct {
	Action     string `json:"action"`
	TopologyId string `json:"topologyId"`
}

func (t *TopologyConsumer) Handle(msgs <-chan amqp.Delivery) {
	for d := range msgs {
		log.Printf("Received a message: %s", d.Body)

		var (
			status int
			out    string
		)

		message := &TopologyHandleMessage{}
		err := json.Unmarshal(d.Body, message)

		topology, _ := t.Db.GetTopologyById(message.TopologyId)
		if topology.ID.Hex() != "" {
			switch message.Action {
			case "stop":
				if viper.GetString("generator.mode") == "swarm" {
					status, out = swarm.Stop(topology)
				} else {
					status, out = compose.Stop(topology)
				}
				break
			case "delete":
				if viper.GetString("generator.mode") == "swarm" {
					status, out = swarm.Delete(topology)
				} else {
					status, out = compose.Delete(topology)
				}
				break

			default:
				status, out = 404, fmt.Sprintf("Action '%s' not found", message.Action)
			}

			log.Printf("Process result %d [%s]", status, out)
		} else {
			log.Printf("Topology ID: %s not found", message.TopologyId)
		}

		if err != nil {
			log.Printf("Error during unserialize body %s", d.Body)
		}

		d.Ack(false)
	}
}
