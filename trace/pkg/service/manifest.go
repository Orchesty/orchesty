package service

import (
	"encoding/json"
	"fmt"
	"net/http"
	"strings"

	log "github.com/hanaboso/go-log/pkg"

	"trace/pkg/sender"
)

type (
	ManifestAction struct {
		ID          string                 `json:"id"`
		Title       string                 `json:"title"`
		Kind        string                 `json:"kind"`
		InputSchema map[string]interface{} `json:"input_schema"`
	}

	SDK struct {
		Name string `json:"name"`
		URL  string `json:"url"`
	}

	sdkListResponse struct {
		Items []SDK `json:"items"`
	}

	ManifestService interface {
		FetchManifest(token string) ([]ManifestAction, error)
		FetchSDKs(token string) ([]SDK, error)
		RunAction(token string, payload []byte) ([]byte, error)
	}

	manifestService struct {
		sender     sender.HttpSender
		backendURL string
		logger     log.Logger
	}
)

func NewManifestService(sender sender.HttpSender, backendURL string, logger log.Logger) ManifestService {
	return manifestService{sender, strings.TrimRight(backendURL, "/"), logger}
}

func (svc manifestService) FetchManifest(token string) ([]ManifestAction, error) {
	url := sender.NewManifestURL(svc.backendURL)

	headers := map[string]string{
		"Authorization": fmt.Sprintf("Bearer %s", token),
	}

	response, body, err := svc.sender.SendRaw(http.MethodGet, url, headers)
	if err != nil {
		svc.logContext().Error(err)

		return nil, fmt.Errorf("backend unreachable: %w", err)
	}

	if response.StatusCode != http.StatusOK {
		return nil, fmt.Errorf("manifest request failed with status %d", response.StatusCode)
	}

	var actions []ManifestAction
	if err := json.Unmarshal(body, &actions); err != nil {
		svc.logContext().Error(err)

		return nil, fmt.Errorf("failed to parse manifest: %w", err)
	}

	return actions, nil
}

func (svc manifestService) FetchSDKs(token string) ([]SDK, error) {
	url := sender.NewSdksURL(svc.backendURL)

	headers := map[string]string{
		"Authorization": fmt.Sprintf("Bearer %s", token),
	}

	response, body, err := svc.sender.SendRaw(http.MethodGet, url, headers)
	if err != nil {
		svc.logContext().Error(err)

		return nil, fmt.Errorf("backend unreachable: %w", err)
	}

	if response.StatusCode != http.StatusOK {
		return nil, fmt.Errorf("sdks request failed with status %d", response.StatusCode)
	}

	var result sdkListResponse
	if err := json.Unmarshal(body, &result); err != nil {
		svc.logContext().Error(err)

		return nil, fmt.Errorf("failed to parse sdks response: %w", err)
	}

	return result.Items, nil
}

func (svc manifestService) RunAction(token string, payload []byte) ([]byte, error) {
	url := sender.NewMcpRunURL(svc.backendURL)

	headers := map[string]string{
		"Authorization": fmt.Sprintf("Bearer %s", token),
		"Content-Type":  "application/json",
	}

	response, body, err := svc.sender.SendRawWithBody(http.MethodPost, url, headers, payload)
	if err != nil {
		return nil, fmt.Errorf("mcp/run request failed: %w", err)
	}

	if response.StatusCode != http.StatusOK {
		return nil, fmt.Errorf("mcp/run returned status %d: %s", response.StatusCode, string(body))
	}

	return body, nil
}

func (svc manifestService) logContext() log.Logger {
	return svc.logger.WithFields(map[string]interface{}{
		"service": "TRACE",
		"type":    "Manifest",
	})
}
