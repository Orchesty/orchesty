package rabbitmq

import (
	"bytes"
	"encoding/json"
	"fmt"
	"io"
	"net/http"
	"net/url"
	"time"

	"cloud-controller/pkg/config"
	"cloud-controller/pkg/models"
)

type Client struct {
	httpClient *http.Client
}

func NewClient() *Client {
	return &Client{
		httpClient: &http.Client{Timeout: 15 * time.Second},
	}
}

func (c *Client) CreateVHost(dto *models.InstanceDTO) (bool, error) {
	payload := map[string]string{
		"description": fmt.Sprintf("'ocInstanceDisplayName: %s'", dto.InstanceDisplayName),
	}

	if err := c.sendRequest(http.MethodPut, fmt.Sprintf("/api/vhosts/%s", url.PathEscape(dto.Instance)), payload); err != nil {
		return false, err
	}

	return true, nil
}

func (c *Client) CreateUser(dto *models.InstanceDTO) (bool, error) {
	payload := map[string]string{
		"password": dto.RabbitPassword,
		"tags":     "monitoring",
	}

	if err := c.sendRequest(http.MethodPut, fmt.Sprintf("/api/users/%s", url.PathEscape(dto.Instance)), payload); err != nil {
		return false, err
	}

	return true, nil
}

func (c *Client) SetPermissions(dto *models.InstanceDTO) (bool, error) {
	payload := map[string]string{
		"configure": ".*",
		"write":     ".*",
		"read":      ".*",
	}

	if err := c.sendRequest(
		http.MethodPut,
		fmt.Sprintf("/api/permissions/%s/%s", url.PathEscape(dto.Instance), url.PathEscape(dto.Instance)),
		payload,
	); err != nil {
		return false, err
	}

	if err := c.sendRequest(
		http.MethodPut,
		fmt.Sprintf("/api/permissions/%s/%s", url.PathEscape(dto.Instance), url.PathEscape(config.RabbitMQ.AdminUser)),
		payload,
	); err != nil {
		return false, err
	}

	return true, nil
}

func (c *Client) DeleteUser(instance string) (bool, error) {
	if err := c.sendRequest(http.MethodDelete, fmt.Sprintf("/api/users/%s", url.PathEscape(instance)), nil); err != nil {
		return false, err
	}

	return true, nil
}

func (c *Client) DeleteVHost(instance string) (bool, error) {
	if err := c.sendRequest(http.MethodDelete, fmt.Sprintf("/api/vhosts/%s", url.PathEscape(instance)), nil); err != nil {
		return false, err
	}

	return true, nil
}

func (c *Client) Health() error {
	return c.sendRequest(http.MethodGet, "/api/overview", nil)
}

func (c *Client) sendRequest(method, path string, data any) error {
	var body io.Reader
	if data != nil {
		payload, err := json.Marshal(data)
		if err != nil {
			return err
		}

		body = bytes.NewBuffer(payload)
	}

	requestURL := fmt.Sprintf("http://%s:%s%s", config.RabbitMQ.Hostname, config.RabbitMQ.ManagementPort, path)
	req, err := http.NewRequest(method, requestURL, body)
	if err != nil {
		return err
	}

	req.SetBasicAuth(config.RabbitMQ.AdminUser, config.RabbitMQ.AdminPass)
	req.Header.Set("Content-Type", "application/json")

	resp, err := c.httpClient.Do(req)
	if err != nil {
		config.Logger.Error(err)
		return err
	}
	defer resp.Body.Close()

	if resp.StatusCode != http.StatusOK && resp.StatusCode != http.StatusCreated && resp.StatusCode != http.StatusNoContent {
		respBody, errRead := io.ReadAll(resp.Body)
		if errRead != nil {
			config.Logger.Error(fmt.Errorf("failed to read response body: %w", errRead))
		}
		err = fmt.Errorf("error status code %d, reason: %s, data: %s", resp.StatusCode, resp.Status, string(respBody))
		config.Logger.Error(err)
		return err
	}

	return nil
}
