package router

import (
	"net/http"

	"github.com/julienschmidt/httprouter"
)

type snapshotItem struct {
	NodeId        string `json:"nodeId"`
	NodeName      string `json:"nodeName"`
	TopologyId    string `json:"topologyId"`
	ApplicationId string `json:"applicationId"`
	Messages      int    `json:"messages"`
}

func Snapshot(writer http.ResponseWriter, _ *http.Request, _ httprouter.Params, container Container) {
	nodes, err := container.Mongo.Snapshot()
	if err != nil {
		errorResponse(writer, err)
		return
	}

	totalMessages := 0
	items := make([]snapshotItem, 0, len(nodes))

	for _, node := range nodes {
		totalMessages += node.Messages
		items = append(items, snapshotItem{
			NodeId:        node.Id.NodeId,
			NodeName:      node.Id.NodeName,
			TopologyId:    node.TopologyId,
			ApplicationId: node.ApplicationId,
			Messages:      node.Messages,
		})
	}

	response(writer, struct {
		TotalMessages int            `json:"totalMessages"`
		Items         []snapshotItem `json:"items"`
	}{
		TotalMessages: totalMessages,
		Items:         items,
	})
}
