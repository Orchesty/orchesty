package model

type ProcessDto struct {
	Body    string `json:"body"`
	Headers map[string]interface{}
}
