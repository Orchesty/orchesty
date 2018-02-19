package probe

import (
	"bytes"
	"encoding/json"
	"fmt"
	"hanaboso/utils/topology"
	"io/ioutil"
	"net/http"
	"path/filepath"
	"testing"
	"time"

	"github.com/stretchr/testify/assert"
)

type storageMock struct {
	Data map[string][]byte
}

func (r *storageMock) Set(key string, value []byte) error {
	r.Data[key] = value
	return nil
}
func (r *storageMock) Get(key string) (string, error) {
	if r.Data[key] == nil {
		return "", fmt.Errorf("key not found")
	}
	return string(r.Data[key]), nil
}
func (r *storageMock) Delete(key string) error {
	delete(r.Data, key)
	return nil
}
func (r *storageMock) Keys() ([]string, error) {
	keys := make([]string, len(r.Data))
	i := 0
	for k := range r.Data {
		keys[i] = k
		i++
	}
	return keys, nil
}

type checkerMock struct{}

func (c *checkerMock) Check(br BridgeInfo, resultsChannel chan<- BridgeInfo) {
	br.Status = true
	br.Message = "OK"
	br.Code = 200

	resultsChannel <- br
}

// TestServer tests server routes and their handling
func TestServer(t *testing.T) {
	emptyMap := make(map[string][]byte)
	storage := storageMock{Data: emptyMap}
	checker := checkerMock{}

	srv := Server{Storage: &storage, CheckerSvc: &checker}
	go srv.Start(5555)
	defer srv.Stop()

	host := "http://localhost:5555"
	var client = http.Client{Timeout: time.Second * 1}

	// List should be empty
	response, _ := client.Get(host + "/topology/list")
	defer response.Body.Close()
	body, _ := ioutil.ReadAll(response.Body)
	assert.Equal(t, "{\"status\":true,\"data\":\"\"}", string(body))

	// Remove should not be possible for non-existing topology
	response, _ = client.Get(host + "/topology/remove?topologyId=XYZ")
	assert.Equal(t, http.StatusBadRequest, response.StatusCode)

	// Status should not be possible for non-existing topology
	response, _ = client.Get(host + "/topology/status?topologyId=XYZ")
	assert.Equal(t, http.StatusBadRequest, response.StatusCode)

	// Add should fail when topology json is missing
	response, _ = client.Get(host + "/topology/add")
	assert.Equal(t, http.StatusBadRequest, response.StatusCode)

	// Load topology from file
	var topo topology.TopologyJson
	filePath, _ := filepath.Abs("../../data/example.topology.json")
	topoData, err := ioutil.ReadFile(filePath)
	if err != nil {
		assert.Nil(t, err, "Could not load topology file.")
		return
	}
	json.Unmarshal(topoData, &topo)

	// Add topology should success
	addReq, _ := http.NewRequest("POST", host+"/topology/add", bytes.NewBuffer(topoData))
	addReq.Header.Set("Content-Type", "application/json")
	response, _ = client.Do(addReq)
	assert.Equal(t, http.StatusOK, response.StatusCode)

	// List should contain 1 topo
	response, _ = client.Get(host + "/topology/list")
	defer response.Body.Close()
	body, _ = ioutil.ReadAll(response.Body)
	assert.Equal(t, "{\"status\":true,\"data\":\""+topo.TopologyId+"\"}", string(body))

	// Add topology should replace the previous with same id
	response, _ = client.Do(addReq)
	assert.Equal(t, http.StatusOK, response.StatusCode)

	// List should contain 1 topo
	response, _ = client.Get(host + "/topology/list")
	defer response.Body.Close()
	body, _ = ioutil.ReadAll(response.Body)
	assert.Equal(t, "{\"status\":true,\"data\":\""+topo.TopologyId+"\"}", string(body))

	// Status should return json with results for bridges in topology
	var checkResult probeStatusResponse
	response, _ = client.Get(host + "/topology/status?topologyId=" + topo.TopologyId)
	assert.Equal(t, http.StatusOK, response.StatusCode)
	defer response.Body.Close()
	statusBody, _ := ioutil.ReadAll(response.Body)
	json.Unmarshal(statusBody, &checkResult)
	assert.Equal(t, topo.TopologyId, checkResult.Id)
	assert.True(t, checkResult.Status)
	assert.Equal(t, "2 of 2 bridges are ready", checkResult.Message)
	assert.Len(t, checkResult.Nodes, 2)

	// Remove should delete existing topology
	response, _ = client.Get(host + "/topology/remove?topologyId=" + topo.TopologyId)
	assert.Equal(t, http.StatusOK, response.StatusCode)

	// List should be empty
	response, _ = client.Get(host + "/topology/list")
	defer response.Body.Close()
	body, _ = ioutil.ReadAll(response.Body)
	assert.Equal(t, "{\"status\":true,\"data\":\"\"}", string(body))
}
