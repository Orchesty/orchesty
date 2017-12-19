package probe

import (
	"testing"
	"fmt"
	"net/http"
	"time"
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

type checkerMock struct {}

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
	srv.Start(5555)

	host := "http://localhost:5555"
	var client = http.Client{Timeout: time.Second * 1}

	request, _ := http.NewRequest("GET", host+ "/topology/list", nil)
	response, _ := client.Do(request)

	fmt.Println(response)
}
