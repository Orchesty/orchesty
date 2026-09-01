package sender

import (
	"bytes"
	"encoding/json"
	"fmt"
	"io"
	"net/http"
	"strings"
	"time"

	"cron/pkg/config"

	log "github.com/hanaboso/go-log/pkg"
)

const (
	contentType     = "Content-Type"
	applicationJson = "application/json; charset=utf-8"
)

type (
	HttpSender interface {
		IsConnected() bool
		Send(method, url string, content interface{}, headers map[string]string) (*http.Response, error)
	}

	httpSender struct {
		client  *http.Client
		logger  log.Logger
		baseURL string
	}
)

func NewHttpSender(client *http.Client, logger log.Logger, baseURL string) HttpSender {
	return httpSender{client, logger, baseURL}
}

func (sender httpSender) IsConnected() bool {
	if _, err := sender.Send(http.MethodGet, "status", nil, nil); err != nil {
		sender.logContext().Error(err)

		return false
	}

	return true
}

func (sender httpSender) Send(method, url string, content interface{}, headers map[string]string) (*http.Response, error) {
	url = fmt.Sprintf("%s/%s", sender.baseURL, url)
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

	for key, value := range headers {
		request.Header.Add(key, value)
	}

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

	requestHeadersMap := map[string]string{}

	for key, value := range request.Header {
		if key == config.OrchestyApiKeyHeader {
			requestHeadersMap[key] = "*****"
		} else {
			requestHeadersMap[key] = value[0]
		}
	}

	requestHeaders, err := sender.jsonEncode(requestHeadersMap)

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
		"[%.3f s] [HTTP %d] [%s %s] (%s) (%s) (%s)",
		timeTwo.Sub(timeOne).Seconds(),
		response.StatusCode,
		request.Method,
		request.URL.String(),
		requestBody,
		strings.Trim(requestHeaders.String(), "\n"),
		strings.Trim(string(responseBody), "\n"),
	)

	if err = response.Body.Close(); err != nil {
		sender.logContext().Error(err)

		return nil, err
	}

	response.Body = io.NopCloser(bytes.NewBuffer(responseBody))

	return response, nil
}

func (sender httpSender) jsonEncode(content interface{}) (bytes.Buffer, error) {
	var body bytes.Buffer

	if err := json.NewEncoder(&body).Encode(content); err != nil {
		sender.logContext().Error(err)

		return body, err
	}

	return body, nil
}

func (sender httpSender) logContext() log.Logger {
	return sender.logger.WithFields(map[string]interface{}{
		"service": "CRON",
		"type":    "Sender",
	})
}
