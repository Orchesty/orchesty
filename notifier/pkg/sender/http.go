package sender

import (
	"bytes"
	"encoding/json"
	"fmt"
	"io"
	"net/http"
	"strings"
	"time"

	log "github.com/hanaboso/go-log/pkg"
)

const (
	contentType     = "Content-Type"
	applicationJson = "application/json; charset=utf-8"
)

type (
	HttpSender interface {
		Send(method, url string, content interface{}) (*http.Response, error)
	}

	httpSender struct {
		client *http.Client
		logger log.Logger
	}
)

func NewHttpSender(client *http.Client, logger log.Logger) HttpSender {
	return httpSender{client, logger}
}

func (sender httpSender) Send(method, url string, content interface{}) (*http.Response, error) {
	body, err := sender.jsonEncode(content)
	requestBody := strings.Trim(body.String(), "\n")

	if err != nil {
		sender.logContext().Error(err)

		return nil, err
	}

	request, err := http.NewRequest(method, url, &body)

	if err != nil {
		sender.logContext().Error(err)

		return nil, err
	}

	request.Header.Add(contentType, applicationJson)

	timeOne := time.Now()
	response, err := sender.client.Do(request)
	timeTwo := time.Now()

	if err != nil {
		sender.logContext().Error(err)
		sender.logContext().Info(
			"[%.3f s] [HTTP 500] [%s %s] (%s)",
			timeTwo.Sub(timeOne).Seconds(),
			request.Method,
			request.URL.String(),
			requestBody,
		)

		return nil, err
	}

	responseBody, _ := io.ReadAll(response.Body)

	sender.logContext().Info(
		"[%.3f s] [HTTP %d] [%s %s] (%s) (%s)",
		timeTwo.Sub(timeOne).Seconds(),
		response.StatusCode,
		request.Method,
		request.URL.String(),
		requestBody,
		strings.Trim(string(responseBody), "\n"),
	)

	if err = response.Body.Close(); err != nil {
		sender.logContext().Error(err)

		return nil, err
	}

	response.Body = io.NopCloser(bytes.NewBuffer(responseBody))

	if response.StatusCode >= 400 {
		return response, fmt.Errorf("HTTP %d: %s", response.StatusCode, strings.Trim(string(responseBody), "\n"))
	}

	return response, nil
}

func (sender httpSender) jsonEncode(content interface{}) (bytes.Buffer, error) {
	var body bytes.Buffer

	if err := json.NewEncoder(&body).Encode(content); err != nil {
		sender.logContext().Error(err)

		return body, fmt.Errorf("failed to encode JSON: %w", err)
	}

	return body, nil
}

func (sender httpSender) logContext() log.Logger {
	return sender.logger.WithFields(map[string]interface{}{
		"service": "NOTIFIER",
		"type":    "Sender",
	})
}
