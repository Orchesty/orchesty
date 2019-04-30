package utils

import (
	"bytes"
	"github.com/stretchr/testify/assert"
	"io/ioutil"
	"net/http"
	"testing"
)

func TestGetBodyFromStream(t *testing.T) {
	reader := ioutil.NopCloser(bytes.NewBuffer([]byte("{}")))
	h := http.Header{}
	h.Add("content-type", "application/json")
	r := &http.Request{Body: reader, Header: h}

	res := GetBodyFromStream(r)
	assert.Equal(t, []byte("{}"), res)
}

func TestValidateBody(t *testing.T) {
	reader := ioutil.NopCloser(bytes.NewBuffer([]byte("{}")))
	h := http.Header{}
	h.Add("content-type", "application/json")
	r := &http.Request{Body: reader, Header: h}

	res := ValidateBody(r)
	assert.Nil(t, res)

	h = http.Header{}
	h.Add("content-type", "application/xml")
	r = &http.Request{Body: reader, Header: h}

	res = ValidateBody(r)
	assert.Nil(t, res)
}

func TestValidateJSON(t *testing.T) {
	res := ValidateJSON([]byte("{}"))
	assert.Nil(t, res)

	res = ValidateJSON([]byte("[]"))
	assert.Nil(t, res)
}

func TestValidateJSONFailed(t *testing.T) {
	res := ValidateJSON([]byte("fail"))
	assert.NotNil(t, res)
}
