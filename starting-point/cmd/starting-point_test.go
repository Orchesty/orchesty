package main

import (
	"fmt"
	"github.com/stretchr/testify/assert"
	"io/ioutil"
	"net/http"
	"testing"
	"time"
)

func TestLimiterApp(t *testing.T) {
	stopTest := make(chan bool, 1)
	go timeoutExit(t, stopTest)

	// run app and give it some time to init tcp server
	go main()
	time.Sleep(time.Millisecond * 50)

	// send fake request
	go simulateTraffic(t, stopTest)

	// wait for stopTest message
	<-stopTest
}

func timeoutExit(t *testing.T, stopTest chan bool) {
	time.Sleep(time.Second * 5)
	assert.Fail(t, "Test exceeded max permitted duration limit")
	stopTest <- true
}

func simulateTraffic(t *testing.T, stopTest chan bool) {
	resp, err := http.Get("http://127.0.0.127:80/status")
	if err != nil {
		fmt.Println(err)
	}

	fmt.Println(resp)
	body, err := ioutil.ReadAll(resp.Body)
	// handling error and doing stuff with body that needs to be unit tested
	if err != nil {
		fmt.Println(err)
	}

	if assert.Equal(t, "{\"status\":\"OK\"}\n", string(body)) {
		stopTest <- true
	}
}
