package utils

import (
	"bytes"
	"encoding/json"
	"fmt"
	"io/ioutil"
	"net/http"

	log "github.com/sirupsen/logrus"
)

const contentType = "content-type"
const jsonType = "application/json"
const xmlType = "application/xml"

// GetBodyFromStream returns []bytes from stream and rewind stream back
func GetBodyFromStream(r *http.Request) (b []byte) {
	b, err := ioutil.ReadAll(r.Body)
	if err != nil {
		log.Error(fmt.Sprintf("Convert stream to []byte error: %s", err))
	}

	if r.Body.Close() != nil {
		log.Error(fmt.Sprintf("Close stream error: %s", err))
	}

	r.Body = ioutil.NopCloser(bytes.NewBuffer(b))

	return
}

// ValidateBody validates struct of request body by content-type
func ValidateBody(r *http.Request) (err error) {
	contentType := r.Header.Get(contentType)
	body := GetBodyFromStream(r)
	switch contentType {
	case jsonType:
		return ValidateJSON(body)
	case xmlType:
		// TODO validate xml
		return nil
	default:
		return ValidateJSON(body)
	}
}

// ValidateJSON Validates json on request body
func ValidateJSON(body []byte) (err error) {
	data := make(map[string]interface{})
	emptyData := make([]interface{}, 0)

	// Check if is json data
	err = json.Unmarshal(body, &data)
	if err == nil {
		data = make(map[string]interface{})
		return
	}

	// Check if is empty array
	err = json.Unmarshal(body, &emptyData)
	if err == nil {
		emptyData = emptyData[:0]
		return
	}

	return err
}
