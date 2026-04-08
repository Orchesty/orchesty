package service

import "encoding/json"

const (
	TypeToken    = "token"
	TypeProvider = "provider"
	TypeRequest  = "request"
	TypeResponse = "response"
	TypeError    = "error"
)

type (
	Message struct {
		Type string          `json:"type"`
		Data json.RawMessage `json:"data"`
	}

	TokenData struct {
		Token string `json:"token"`
	}

	ProviderData struct {
		Worker            string `json:"worker"`
		WorkerProviderURL string `json:"workerProviderUrl"`
		User              string `json:"user"`
	}

	RequestData struct {
		Content string `json:"content"`
	}

	ResponseData struct {
		Content string `json:"content"`
	}

	ErrorData struct {
		Code    int    `json:"code"`
		Message string `json:"message"`
	}
)
