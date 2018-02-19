package response

import (
	"net/http"
	"encoding/json"
	"github.com/docker/docker/api/types"
)

type RequestResponse struct {
	Message    string            `json:"message"`
	DockerInfo []types.Container `json:"docker-info,omitempty"`
}

func (r *RequestResponse) Prepare() []byte {
	message, _ := json.MarshalIndent(r, "", " ")

	return message
}

func ResponseWithJSON(w http.ResponseWriter, json []byte, code int) {
	w.Header().Set("Content-Type", "application/json; charset=utf-8")
	w.WriteHeader(code)
	w.Write(json)
}

