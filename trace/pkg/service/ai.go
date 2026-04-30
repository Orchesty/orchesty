package service

import (
	"encoding/json"
	"fmt"
	"net/http"

	log "github.com/hanaboso/go-log/pkg"

	"trace/pkg/sender"
)

const (
	platformServiceType   = "trace-ai-provider"
	platformServiceMethod = "trace"
)

type (
	AIService interface {
		SendChat(token, userID, system string, history []ChatTurn) (string, error)
	}

	aiService struct {
		httpSender sender.HttpSender
		backendURL string
		logger     log.Logger
	}

	platformServiceRequest struct {
		System   string     `json:"system,omitempty"`
		Messages []ChatTurn `json:"messages"`
		User     string     `json:"user"`
	}

	platformServiceResponse struct {
		Response string `json:"response"`
	}
)

func NewAIService(httpSender sender.HttpSender, backendURL string, logger log.Logger) AIService {
	return aiService{httpSender: httpSender, backendURL: backendURL, logger: logger}
}

// SendChat dispatches the conversation to the bound AI provider through
// platform-services. The provider is expected to return a JSON object with a
// "response" string containing the model's raw textual reply (which the caller
// then parses as either an action envelope or a reply envelope).
func (svc aiService) SendChat(token, userID, system string, history []ChatTurn) (string, error) {
	reqBody, err := json.Marshal(platformServiceRequest{
		System:   system,
		Messages: history,
		User:     userID,
	})
	if err != nil {
		return "", fmt.Errorf("failed to marshal request: %w", err)
	}

	url := sender.NewPlatformServiceCallURL(svc.backendURL, platformServiceType, platformServiceMethod)

	svc.logContext().Info("Sending chat via platform-services: %s (turns=%d)", url, len(history))

	resp, body, err := svc.httpSender.SendRawWithBody(
		http.MethodPost, url,
		map[string]string{
			"Content-Type":  "application/json",
			"Authorization": fmt.Sprintf("Bearer %s", token),
		},
		reqBody,
	)
	if err != nil {
		return "", fmt.Errorf("platform-services request failed: %w", err)
	}

	if resp.StatusCode == http.StatusUnauthorized || resp.StatusCode == http.StatusForbidden {
		return "", fmt.Errorf("platform-services returned status %d: %s: %w", resp.StatusCode, string(body), ErrUnauthorized)
	}

	if resp.StatusCode != http.StatusOK {
		return "", fmt.Errorf("platform-services returned status %d: %s", resp.StatusCode, string(body))
	}

	var tr platformServiceResponse
	if err := json.Unmarshal(body, &tr); err != nil {
		return "", fmt.Errorf("failed to parse platform-services response: %w", err)
	}

	return tr.Response, nil
}

func (svc aiService) logContext() log.Logger {
	return svc.logger.WithFields(map[string]interface{}{
		"service": "TRACE",
		"type":    "AI",
	})
}
