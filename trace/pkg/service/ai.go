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
		SendPrompt(token, userID, prompt string) (string, error)
	}

	aiService struct {
		httpSender sender.HttpSender
		backendURL string
		logger     log.Logger
	}

	platformServiceRequest struct {
		Request string `json:"request"`
		User    string `json:"user"`
	}

	platformServiceResponse struct {
		Response string `json:"response"`
	}
)

func NewAIService(httpSender sender.HttpSender, backendURL string, logger log.Logger) AIService {
	return aiService{httpSender: httpSender, backendURL: backendURL, logger: logger}
}

func (svc aiService) SendPrompt(token, userID, prompt string) (string, error) {
	reqBody, err := json.Marshal(platformServiceRequest{Request: prompt, User: userID})
	if err != nil {
		return "", fmt.Errorf("failed to marshal request: %w", err)
	}

	url := sender.NewPlatformServiceCallURL(svc.backendURL, platformServiceType, platformServiceMethod)

	svc.logContext().Info("Sending prompt via platform-services: %s", url)

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
