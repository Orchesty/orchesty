package probe

import (
	"net/http"
	"fmt"
	"time"
	"io/ioutil"
	"log"
)

var checkHttpClient = http.Client{Timeout: time.Second * 10}

func Check(br BridgeInfo, resultsChannel chan<- BridgeInfo) {
	log.Println("Sending request GET to " + br.Url)

	request, err := http.NewRequest("GET", br.Url, nil)

	if err != nil {
		br.Code = http.StatusInternalServerError
		br.Message = fmt.Sprintf("Error creating check request: %s", err)

		log.Println("Response received.", br)

		resultsChannel <- br
		return
	}

	response, err := checkHttpClient.Do(request)

	if err != nil {
		br.Code = http.StatusServiceUnavailable
		br.Message = fmt.Sprintf("Error checking bridge: %s", err)

		log.Println("Error receiving response.", br)

		resultsChannel <- br
		return
	}

	bodyBytes, _ := ioutil.ReadAll(response.Body)
	defer response.Body.Close()

	br.Code = response.StatusCode
	br.Message = string(bodyBytes)

	resultsChannel <- br

	log.Println("Response received.", br)
}
