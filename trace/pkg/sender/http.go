package sender

import (
	"bytes"
	"fmt"
	"io"
	"net/http"
	"strings"
	"time"

	log "github.com/hanaboso/go-log/pkg"
)

type (
	HttpSender interface {
		SendRaw(method, url string, headers map[string]string) (*http.Response, []byte, error)
		SendRawWithBody(method, url string, headers map[string]string, body []byte) (*http.Response, []byte, error)
		IsConnected(url string) bool
	}

	httpSender struct {
		client *http.Client
		logger log.Logger
	}
)

func NewHttpSender(client *http.Client, logger log.Logger) HttpSender {
	return httpSender{client, logger}
}

func (sender httpSender) SendRaw(method, url string, headers map[string]string) (*http.Response, []byte, error) {
	return sender.doRequest(method, url, headers, nil)
}

func (sender httpSender) SendRawWithBody(method, url string, headers map[string]string, body []byte) (*http.Response, []byte, error) {
	return sender.doRequest(method, url, headers, body)
}

func (sender httpSender) doRequest(method, url string, headers map[string]string, body []byte) (*http.Response, []byte, error) {
	var bodyReader io.Reader
	if body != nil {
		bodyReader = bytes.NewReader(body)
	}

	request, err := http.NewRequest(method, url, bodyReader)
	if err != nil {
		sender.logContext().Error(err)

		return nil, nil, err
	}

	for key, value := range headers {
		request.Header.Set(key, value)
	}

	timeOne := time.Now()
	response, err := sender.client.Do(request)
	timeTwo := time.Now()

	if err != nil {
		sender.logContext().Error(err)
		sender.logContext().Info(
			"[%.3f s] [HTTP 500] [%s %s]",
			timeTwo.Sub(timeOne).Seconds(),
			request.Method,
			request.URL.String(),
		)

		return nil, nil, err
	}

	responseBody, _ := io.ReadAll(response.Body)
	_ = response.Body.Close()

	sender.logContext().Info(
		"[%.3f s] [HTTP %d] [%s %s] (%s)",
		timeTwo.Sub(timeOne).Seconds(),
		response.StatusCode,
		request.Method,
		request.URL.String(),
		strings.Trim(string(responseBody), "\n"),
	)

	return response, responseBody, nil
}

func (sender httpSender) IsConnected(url string) bool {
	response, _, err := sender.doRequest(http.MethodGet, url, nil, nil)

	return err == nil && response.StatusCode == http.StatusOK
}

func (sender httpSender) logContext() log.Logger {
	return sender.logger.WithFields(map[string]interface{}{
		"service": "TRACE",
		"type":    "Sender",
	})
}

func NewStatusURL(backendURL string) string {
	return fmt.Sprintf("%s/api/status", strings.TrimRight(backendURL, "/"))
}

func NewManifestURL(backendURL string) string {
	return fmt.Sprintf("%s/mcp/manifest.json", strings.TrimRight(backendURL, "/"))
}

func NewMcpRunURL(backendURL string) string {
	return fmt.Sprintf("%s/mcp/run", strings.TrimRight(backendURL, "/"))
}

func NewSdksURL(backendURL string) string {
	return fmt.Sprintf("%s/api/sdks", strings.TrimRight(backendURL, "/"))
}
