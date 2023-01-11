package services

import (
	"detector/pkg/config"
	"fmt"
	log "github.com/hanaboso/go-log/pkg"
	"github.com/hanaboso/go-mongodb"
	"go.mongodb.org/mongo-driver/bson/primitive"
	"regexp"
)

type ConsumerChecker struct {
	logger     log.Logger
	connection *mongodb.Connection
}
type Node struct {
	ID         primitive.ObjectID `bson:"_id" json:"id"`
	TopologyId string             `bson:"topology" json:"topology"`
}

const (
	Queue_Limiter      = "pipes.limiter"
	Queue_Repeater     = "pipes.repeater"
	Queue_MultiCounter = "pipes.multi-counter"
)

const (
	Service_Limiter      = "limiter"
	Service_Repeater     = "repeater"
	Service_MultiCounter = "multi-counter"
)

var services = map[string]string{
	Queue_Limiter:      Service_Limiter,
	Queue_Repeater:     Service_Repeater,
	Queue_MultiCounter: Service_MultiCounter,
}

func (c ConsumerChecker) ConsumerCheck(queues []Queue) {
	names := make(map[string]struct{})

	ctx, cancel := c.connection.Context()
	defer cancel()
	reg := regexp.MustCompile("node\\.(\\d\\w+)\\.\\d+")

	for _, queue := range queues {
		serviceName, isService := services[queue.Name]

		var name string
		if queue.Consumers == 0 {
			if isService {
				name = serviceName
			} else {
				nodeMatch := reg.FindStringSubmatch(queue.Name)

				if len(nodeMatch) > 1 {
					nodeId := nodeMatch[1]
					primNodeId, err := primitive.ObjectIDFromHex(nodeId)
					var node Node

					if err != nil {
						c.logger.Error(fmt.Errorf("id '%s' not in valid format", nodeId))
					}

					err = c.connection.
						Database.
						Collection(config.Mongo.Node).
						FindOne(ctx, primitive.D{
							{"_id", primNodeId},
						}).
						Decode(&node)

					if err != nil {
						c.logger.Error(fmt.Errorf("node with id '%s' not found", nodeId))
						c.logger.Error(fmt.Errorf("%s", err))
						continue
					}

					name = node.TopologyId
				} else {
					continue
				}
			}

			names[name] = struct{}{}
		}
	}

	for name := range names {
		c.logger.Error(fmt.Errorf("service [%s] has no connection to rabbitmq", name))
	}
}

func NewConsumerCheckerSvc(logger log.Logger, connection *mongodb.Connection) ConsumerChecker {
	return ConsumerChecker{
		logger,
		connection,
	}
}
