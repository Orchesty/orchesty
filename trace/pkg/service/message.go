package service

import "encoding/json"

const (
	TypeToken    = "token"
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

	// ChatTurn represents one entry in the conversation history sent to the LLM.
	// Roles map to the OpenAI/Responses-API conventions: "user" or "assistant".
	ChatTurn struct {
		Role    string `json:"role"`
		Content string `json:"content"`
	}
)
