package rabbit

import (
	"fmt"
	"github.com/hanaboso/pipes/bridge/pkg/model"
)

func Exchange(shard model.NodeShard) string {
	return fmt.Sprintf("node.%s.hx", shard.Node.ID)
}

func Queue(shard model.NodeShard) string {
	return fmt.Sprintf("node.%s.%d", shard.Node.ID, shard.Index)
}

func RoutingKey(_ model.NodeShard) string {
	return "1" // TODO tohle se rozsype pokud se přidá různorodost při recreate / rebind
}
