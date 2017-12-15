package probe

import (
	"strconv"
	"net/http"
	"encoding/json"
	"fmt"
	"io/ioutil"
)

const Port = 8007
const TopologyAddPath = "/topology/add"
const TopologyListPath = "/topology/list"
const TopologyRemovePath = "/topology/remove"
const TopologyStatusPath = "/topology/status"

// TopologiesMap is persisted data structure to keep information about topologies and their nodes
type TopologiesMap map[string][]BridgeInfo

// BridgeInfo is the struct to keep information about single bridge
type BridgeInfo struct {
	Id       string `json:"id"`
	NodeId   string `json:"node_id"`
	NodeName string `json:"node_name"`
	Status   bool   `json:"status"`
	Url      string `json:"url"`
	Code     int    `json:"code"`
	Message  string `json:"message"`
}

type probeStatusResponse struct {
	Id      string       `json:"id"`
	Status  bool         `json:"status"`
	Message string       `json:"message"`
	Nodes   []BridgeInfo `json:"nodes"`
}

type responseBody struct {
	Status bool   `json:"status"`
	Data   string `json:"data"`
}

// Server is the probe's http server
type Server struct {
	Topologies TopologiesMap
}

// Start starts the probe's http server and registers routes
func (probe *Server) Start() {

	http.Handle(TopologyAddPath, jsonResponse(http.HandlerFunc(probe.handleAddRequest)))
	http.Handle(TopologyListPath, jsonResponse(http.HandlerFunc(probe.handleListRequest)))
	http.Handle(TopologyRemovePath, jsonResponse(http.HandlerFunc(probe.handleRemoveRequest)))
	http.Handle(TopologyStatusPath, jsonResponse(http.HandlerFunc(probe.handleStatusRequest)))

	http.ListenAndServe(":"+strconv.Itoa(Port), nil)
}

func jsonResponse(next http.Handler) http.Handler {
	return http.HandlerFunc(func(w http.ResponseWriter, r *http.Request) {
		w.Header().Add("Content-Type", "application/json")

		next.ServeHTTP(w, r)
	})
}

// handleAddRequest adds given topology configuration to the internal map of maintained topologies
// returns 200 statusCode and topologyId in response if handled well
func (probe *Server) handleAddRequest(res http.ResponseWriter, req *http.Request) {
	var receivedTopology topologyJson

	data, err := ioutil.ReadAll(req.Body)

	if err != nil {
		res.WriteHeader(http.StatusBadRequest)
		res.Write(getErrorResponseBody(err))
		return
	}

	json.Unmarshal(data, &receivedTopology)

	if receivedTopology.ID == "" || len(receivedTopology.Bridges) == 0 {
		res.WriteHeader(http.StatusBadRequest)
		res.Write(getErrorResponseBody(fmt.Errorf("please provide valid topology")))
		return
	}

	bridges := make([]BridgeInfo, len(receivedTopology.Bridges))
	for index, element := range receivedTopology.Bridges {
		b := BridgeInfo{
			Id: element.ID,
			NodeId: element.NodeId,
			NodeName: element.NodeName,
			Url: element.Debug.Url,
		}

		bridges[index] = b
	}

	probe.Topologies[receivedTopology.ID] = bridges

	res.WriteHeader(http.StatusOK)
	res.Write(getSuccessResponseBody(receivedTopology.ID))
}

// handleRemoveRequest removes key from topologies map if it exists there
func (probe *Server) handleRemoveRequest(res http.ResponseWriter, req *http.Request) {
	topologyId := req.FormValue("topologyId")

	_, err := probe.getTopology(topologyId)
	if err != nil {
		res.WriteHeader(http.StatusBadRequest)
		res.Write(getErrorResponseBody(err))
		return
	}

	delete(probe.Topologies, topologyId)

	res.WriteHeader(http.StatusOK)
	res.Write(getSuccessResponseBody(topologyId))
}

// handleListRequest returns the json list of all maintained topologies and their bridge's urls
func (probe *Server) handleListRequest(res http.ResponseWriter, req *http.Request) {
	jsonString, err := json.Marshal(probe.Topologies)

	if err != nil {
		res.WriteHeader(http.StatusInternalServerError)
		res.Write(getErrorResponseBody(err))
		return
	}

	res.WriteHeader(http.StatusOK)
	res.Write(getSuccessResponseBody(string(jsonString)))
}

// handleStatusRequest creates http request to all topology nodes and returns the overall result
func (probe *Server) handleStatusRequest(res http.ResponseWriter, req *http.Request) {
	topologyId := req.FormValue("topologyId")

	bridges, err := probe.getTopology(topologyId)
	if err != nil {
		res.WriteHeader(http.StatusBadRequest)
		res.Write(getErrorResponseBody(err))
		return
	}

	results := make(chan BridgeInfo, len(bridges))

	for _, bridge := range bridges {
		go Check(bridge, results)
	}

	total := 0
	ready := 0
	failed := 0
	bridgesStatuses := make([]BridgeInfo, len(bridges))
	for r := 0; r < len(bridges); r++ {
		br := <-results

		total++

		if br.Code == http.StatusOK {
			ready++
		} else {
			failed++
		}

		bridgesStatuses[r] = BridgeInfo{
			Id:       topologyId,
			NodeId:   br.NodeId,
			NodeName: br.NodeName,
			Status:   br.Code != http.StatusOK,
			Url:      br.Url,
			Code:     br.Code,
			Message:  br.Message,
		}
	}

	body := probeStatusResponse{
		Id:      topologyId,
		Status:  ready == total,
		Message: fmt.Sprintf("%d of %d bridges are ready", ready, total),
		Nodes:   bridgesStatuses,
	}

	out, _ := json.Marshal(body)

	res.WriteHeader(http.StatusOK)
	res.Write(out)
}

// getTopology return the bridges information for given topologyId or returns error
func (probe *Server) getTopology(topologyId string) (bridges []BridgeInfo, err error) {
	if topologyId == "" {
		return nil, fmt.Errorf("missing 'topologyId' param")
	}

	bridges, ok := probe.Topologies[topologyId]

	if !ok {
		return nil, fmt.Errorf("unknown topology '%s'", topologyId)
	}

	return bridges, nil
}

// getErrorResponseBody formats the http response body for errors
func getErrorResponseBody(err error) []byte {
	body := responseBody{Status: false, Data: err.Error()}
	out, _ := json.Marshal(body)

	return out
}

// getSuccessResponseBody formats the http success response body
func getSuccessResponseBody(data string) []byte {
	body := responseBody{Status: true, Data: data}
	out, _ := json.Marshal(body)

	return out
}
