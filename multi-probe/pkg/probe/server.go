package probe

import (
	"encoding/json"
	"fmt"
	"io/ioutil"
	"log"
	"net/http"
	"strconv"
	"strings"

	"github.com/go-redis/redis"
)

const TopologyAddPath = "/topology/add"
const TopologyListPath = "/topology/list"
const TopologyRemovePath = "/topology/remove"
const TopologyStatusPath = "/topology/status"

// TopologiesMap is persisted data structure to keep information about topologies and their nodes
type TopologiesMap map[string][]BridgeInfo

// TopologyInfo struct contains information about running topologies
type TopologyInfo struct {
	Bridges []BridgeInfo `json:"bridges"`
}

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

// Server is the http server that checks the statuses of bridges in topology
type Server struct {
	httpServer *http.Server
	Storage    Storage
	CheckerSvc Checker
}

// Start starts the probe's http server and registers routes
func (probe *Server) Start(port int) {

	srv := &http.Server{Addr: ":" + strconv.Itoa(port)}

	http.Handle(TopologyAddPath, jsonResponse(http.HandlerFunc(probe.handleAddRequest)))
	http.Handle(TopologyListPath, jsonResponse(http.HandlerFunc(probe.handleListRequest)))
	http.Handle(TopologyRemovePath, jsonResponse(http.HandlerFunc(probe.handleRemoveRequest)))
	http.Handle(TopologyStatusPath, jsonResponse(http.HandlerFunc(probe.handleStatusRequest)))

	go func() {
		log.Println("Starting probe http server.")

		err := srv.ListenAndServe()
		if err != nil {
			log.Println("Error starting server: ", err.Error())
		} else {
			log.Println("Server listening on port: ", port)
		}
	}()

	probe.httpServer = srv
}

// Stop stops the probe's http server gracefully
func (probe *Server) Stop() {
	log.Println("Stopping probe http server.")
	if err := probe.httpServer.Shutdown(nil); err != nil {
		panic(err) // failure/timeout shutting down the server gracefully
	}
}

// jsonResponse is middleware function that adds json related http headers to http response
func jsonResponse(next http.Handler) http.Handler {
	return http.HandlerFunc(func(w http.ResponseWriter, r *http.Request) {
		w.Header().Add("Content-Type", "application/json")

		next.ServeHTTP(w, r)
	})
}

// handleAddRequest adds given topology configuration to the internal map of maintained topologies
// returns 200 statusCode and topologyId in response if handled well
func (probe *Server) handleAddRequest(res http.ResponseWriter, req *http.Request) {
	var receivedTopology TopologyJson
	var topologyInfo TopologyInfo

	log.Println("Add topology request received.")

	data, err := ioutil.ReadAll(req.Body)

	if err != nil {
		res.WriteHeader(http.StatusBadRequest)
		res.Write(getErrorResponseBody(err))
		return
	}

	err = json.Unmarshal(data, &receivedTopology)
	if err != nil {
		res.WriteHeader(http.StatusBadRequest)
		res.Write(getErrorResponseBody(err))
		return
	}

	if receivedTopology.TopologyId == "" || len(receivedTopology.Bridges) == 0 {
		res.WriteHeader(http.StatusBadRequest)
		res.Write(getErrorResponseBody(fmt.Errorf("please provide valid topology")))
		log.Println("Invalid topology provided.")
		return
	}

	log.Println("Trying to add topology: " + receivedTopology.TopologyId)

	bridges := make([]BridgeInfo, len(receivedTopology.Bridges))
	for index, element := range receivedTopology.Bridges {
		b := BridgeInfo{
			Id:       element.ID,
			NodeId:   element.Label.NodeId,
			NodeName: element.Label.NodeName,
			Url:      element.Debug.Url,
		}

		bridges[index] = b
	}

	topologyInfo.Bridges = bridges
	topologyString, _ := json.Marshal(topologyInfo)

	err = probe.Storage.Set(receivedTopology.TopologyId, topologyString)
	if err != nil {
		msg := "Unable to add topology " + receivedTopology.TopologyId + " Redis err:" + err.Error()
		log.Println(msg, err)
		res.WriteHeader(http.StatusBadRequest)
		res.Write(getErrorResponseBody(fmt.Errorf(msg)))
		return
	}

	log.Println("Added topology: " + receivedTopology.TopologyId)

	res.WriteHeader(http.StatusOK)
	res.Write(getSuccessResponseBody(receivedTopology.TopologyId))
}

// handleRemoveRequest removes key from topologies map if it exists there
func (probe *Server) handleRemoveRequest(res http.ResponseWriter, req *http.Request) {
	topologyId := req.FormValue("topologyId")

	log.Println("Remove topology request received.", topologyId)

	_, err := probe.getTopology(topologyId)
	if err != nil {
		res.WriteHeader(http.StatusBadRequest)
		res.Write(getErrorResponseBody(err))
		return
	}

	err = probe.Storage.Delete(topologyId)
	if err != nil {
		res.WriteHeader(http.StatusBadRequest)
		res.Write(getErrorResponseBody(fmt.Errorf("cannot delete. Error: %s", err)))
		return
	}

	log.Println("Removed topology: " + topologyId)

	res.WriteHeader(http.StatusOK)
	res.Write(getSuccessResponseBody(topologyId))
}

// handleListRequest returns the json list of all maintained topologies and their bridge's urls
func (probe *Server) handleListRequest(res http.ResponseWriter, req *http.Request) {
	log.Println("List topologies request received.")

	topologies, err := probe.Storage.Keys()
	if err != nil {
		res.WriteHeader(http.StatusInternalServerError)
		res.Write(getErrorResponseBody(err))
		return
	}

	log.Println(fmt.Sprintf("Topology list now contains %d topologies", len(topologies)))

	res.WriteHeader(http.StatusOK)
	res.Write(getSuccessResponseBody(strings.Join(topologies, ",")))
}

// handleStatusRequest creates http request to all topology nodes and returns the overall result
func (probe *Server) handleStatusRequest(res http.ResponseWriter, req *http.Request) {
	topologyId := req.FormValue("topologyId")

	log.Println("Status topology request received.", topologyId)

	topo, err := probe.getTopology(topologyId)
	if err != nil {
		res.WriteHeader(http.StatusBadRequest)
		res.Write(getErrorResponseBody(err))
		return
	}

	bridges := topo.Bridges
	results := make(chan BridgeInfo, len(bridges))

	for _, bridge := range bridges {
		go probe.CheckerSvc.Check(bridge, results)
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

		bridgesStatuses[r] = br
	}

	body := probeStatusResponse{
		Id:      topologyId,
		Status:  ready == total,
		Message: fmt.Sprintf("%d of %d bridges are ready", ready, total),
		Nodes:   bridgesStatuses,
	}

	log.Println("Status topology result:", topologyId, " -> ", body.Message)

	out, _ := json.Marshal(body)

	res.WriteHeader(http.StatusOK)
	res.Write(out)
}

// getTopology return the bridges information for given topologyId or returns error
func (probe *Server) getTopology(topologyId string) (topo TopologyInfo, err error) {
	var topoInfo TopologyInfo

	if topologyId == "" {
		return topoInfo, fmt.Errorf("missing 'topologyId' param")
	}

	val, err := probe.Storage.Get(topologyId)
	if err == redis.Nil {
		return topoInfo, fmt.Errorf("cannot find topology '%s'", topologyId)
	}
	if err != nil {
		return topoInfo, fmt.Errorf("error finding topology '%s'. %s", topologyId, err)
	}

	err = json.Unmarshal([]byte(val), &topoInfo)
	if err != nil {
		return topoInfo, fmt.Errorf("error loading topology '%s'. %s", topologyId, err)
	}

	return topoInfo, nil
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
