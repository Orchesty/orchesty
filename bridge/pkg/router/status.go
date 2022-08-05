package router

import (
	"github.com/julienschmidt/httprouter"
	"io/ioutil"
	"net/http"
)

type nodeStatus struct {
	Id     string `json:"id"`
	Name   string `json:"name"`
	Status string `json:"status"` // ok, nok
	Reason string `json:"reason,omitempty"`
}

func Status(writer http.ResponseWriter, _ *http.Request, _ httprouter.Params, container Container) {
	topology := container.Topology
	res := make([]nodeStatus, len(topology.Nodes))

	for i, node := range topology.Nodes {
		status := "ok"
		reason := ""

		if url := node.Settings.TestUrl(); url != "" {
			res, err := http.Get(url)
			if err != nil {
				status = "nok"
				reason = err.Error()
			} else {
				if res.StatusCode >= 300 {
					status = "nok"
					bytes, _ := ioutil.ReadAll(res.Body)
					reason = string(bytes)
				}
			}
		}

		res[i] = nodeStatus{
			Id:     node.ID,
			Name:   node.Name,
			Status: status,
			Reason: reason,
		}
	}

	response(writer, res)
}
