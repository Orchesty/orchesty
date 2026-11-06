package topology

import (
	"github.com/hanaboso/pipes/bridge/pkg/enum"
	"github.com/hanaboso/pipes/bridge/pkg/model"
)

// ReplyPath returns ids of the topology's "main branch": nodes from which a response node
// is reachable, including the response nodes themselves. A synchronous caller (reply-to)
// is only notified about what happens on this branch; side branches (e.g. notifications)
// never answer the caller.
func ReplyPath(topology model.Topology) map[string]bool {
	predecessors := map[string][]string{}
	var queue []string

	for _, node := range topology.Nodes {
		for _, follower := range node.Followers {
			predecessors[follower.Id] = append(predecessors[follower.Id], node.ID)
		}
		if node.Worker == enum.WorkerType_Response {
			queue = append(queue, node.ID)
		}
	}

	onPath := map[string]bool{}
	for len(queue) > 0 {
		id := queue[0]
		queue = queue[1:]
		if onPath[id] {
			continue
		}
		onPath[id] = true
		queue = append(queue, predecessors[id]...)
	}

	return onPath
}
