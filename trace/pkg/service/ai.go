package service

import (
	"encoding/json"
	"fmt"
	"net/http"

	log "github.com/hanaboso/go-log/pkg"

	"trace/pkg/sender"
)

type (
	AIService interface {
		SendPrompt(endpointURL, userID, sdk, prompt string) (string, error)
	}

	aiService struct {
		httpSender sender.HttpSender
		logger     log.Logger
	}

	workerEnvelope struct {
		Body    string            `json:"body"`
		Headers map[string]string `json:"headers"`
	}

	traceRequest struct {
		Request string `json:"request"`
	}

	traceResponse struct {
		Response string `json:"response"`
	}
)

func NewAIService(httpSender sender.HttpSender, logger log.Logger) AIService {
	return aiService{httpSender: httpSender, logger: logger}
}

func (svc aiService) SendPrompt(endpointURL, userID, sdk, prompt string) (string, error) {
	reqBody, err := json.Marshal(traceRequest{Request: prompt})
	if err != nil {
		return "", fmt.Errorf("failed to marshal request: %w", err)
	}

	envelope := workerEnvelope{
		Body: string(reqBody),
		Headers: map[string]string{
			"user": userID,
			"sdk":  sdk,
		},
	}

	envelopeBytes, err := json.Marshal(envelope)
	if err != nil {
		return "", fmt.Errorf("failed to marshal envelope: %w", err)
	}

	svc.logContext().Info("Sending prompt to worker: %s", endpointURL)

	resp, body, err := svc.httpSender.SendRawWithBody(
		http.MethodPost, endpointURL,
		map[string]string{"Content-Type": "application/json"},
		envelopeBytes,
	)
	if err != nil {
		return "", fmt.Errorf("worker request failed: %w", err)
	}

	if resp.StatusCode != http.StatusOK {
		return "", fmt.Errorf("worker returned status %d: %s", resp.StatusCode, string(body))
	}

	var respEnvelope workerEnvelope
	if err := json.Unmarshal(body, &respEnvelope); err != nil {
		return "", fmt.Errorf("failed to parse worker response: %w", err)
	}

	var tr traceResponse
	if err := json.Unmarshal([]byte(respEnvelope.Body), &tr); err != nil {
		return "", fmt.Errorf("failed to parse trace response: %w", err)
	}

	return tr.Response, nil
}

func (svc aiService) logContext() log.Logger {
	return svc.logger.WithFields(map[string]interface{}{
		"service": "TRACE",
		"type":    "AI",
	})
}
