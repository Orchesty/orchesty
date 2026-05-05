package service

import "encoding/json"

const (
	TypeToken         = "token"
	TypeRequest       = "request"
	TypeResponse      = "response"
	TypeError         = "error"
	TypeQuotaExceeded = "quota_exceeded"
)

type (
	Message struct {
		Type string          `json:"type"`
		Data json.RawMessage `json:"data"`
	}

	TokenData struct {
		Token string `json:"token"`
	}

	// RequestData is the user-side WebSocket "request" frame.
	//
	// `Content` is the natural-language message. `ExtraContext` is an
	// optional key/value bag the FE can use to forward client-side state
	// (e.g. the active onboarding stage from sessionStorage) so the LLM
	// can disambiguate "what's next" without a separate stateful service.
	// Keys are case-insensitive and bounded to a small whitelist by the
	// server; unknown keys are ignored.
	RequestData struct {
		Content      string            `json:"content"`
		ExtraContext map[string]string `json:"extraContext,omitempty"`
	}

	ResponseData struct {
		Content string `json:"content"`
	}

	ErrorData struct {
		Code    int    `json:"code"`
		Message string `json:"message"`
	}

	// QuotaData is the payload of a `quota_exceeded` WS frame: structured
	// counters so the FE can render an info card with the daily limit,
	// current usage, and the next reset moment in the user's local TZ.
	// Mirrors the JSON shape returned by `pf-bundles-enterprise`
	// `QuotaExceededException::toPayload()`.
	QuotaData struct {
		Limit   int    `json:"limit"`
		Used    int    `json:"used"`
		ResetAt string `json:"resetAt"`
	}

	// ChatTurn represents one entry in the conversation history sent to the LLM.
	// Roles map to the OpenAI/Responses-API conventions: "user" or "assistant".
	ChatTurn struct {
		Role    string `json:"role"`
		Content string `json:"content"`
	}
)
