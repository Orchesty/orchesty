package probe

import (
	"net/http"
	"fmt"
	"io/ioutil"
	"log"
)

// HttpClient Interface is used by Checker to create http requests and handle responses
type HttpClient interface {
	Do(req *http.Request) (*http.Response, error)
}
// Checker interface stands for possible checker implementations
type Checker interface {
	Check(br BridgeInfo, resultsChannel chan<- BridgeInfo)
}

// Checker is used for checking bridge's statuses
type HttpChecker struct {
	Client HttpClient
}

// Check sends http request to target url and waits for response
// When the response is accepted, it sets BridgeInfo properties according to response headers and body
func (c *HttpChecker) Check(br BridgeInfo, resultsChannel chan<- BridgeInfo) {
	log.Println("Sending request GET to " + br.Url)

	request, err := http.NewRequest("GET", br.Url, nil)

	if err != nil {
		br.Code = http.StatusInternalServerError
		br.Message = fmt.Sprintf("Error creating check request: %s", err)
		log.Println(br.Message, br)

		resultsChannel <- br
		return
	}

	response, err := c.Client.Do(request)

	if err != nil {
		br.Code = http.StatusServiceUnavailable
		br.Message = fmt.Sprintf("Error checking bridge: %s", err)
		log.Println("Error receiving response.", br)

		resultsChannel <- br
		return
	}

	bodyBytes, err := ioutil.ReadAll(response.Body)
	defer response.Body.Close()
	if err != nil {
		br.Code = http.StatusServiceUnavailable
		br.Message = fmt.Sprintf("Could not read bridge response: %s", err)
		log.Println(br.Message, br)

		resultsChannel <- br
		return
	}

	br.Code = response.StatusCode
	br.Message = string(bodyBytes)
	br.Status = response.StatusCode == http.StatusOK

	resultsChannel <- br

	log.Println("Response received.", br)
}
