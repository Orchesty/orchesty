package objectStorage

import (
	"encoding/json"
	"fmt"
	"io"
	"net/http"
	"strings"
	"time"

	"cloud-controller/pkg/config"
	"cloud-controller/pkg/models"
)

const bucketPrefix = "logs-"

type HMACCredentials struct {
	AccessKey string
	SecretKey string
}

type Client struct {
	httpClient *http.Client
}

func NewClient() *Client {
	return &Client{
		httpClient: &http.Client{Timeout: 15 * time.Second},
	}
}

func (c *Client) CreateBucket(dto *models.InstanceDTO) (*HMACCredentials, error) {
	bucketName := bucketPrefix + dto.Instance

	payload := map[string]string{
		"name":     bucketName,
		"location": config.GCS.Location,
	}

	body, err := json.Marshal(payload)
	if err != nil {
		return nil, err
	}

	url := fmt.Sprintf("%s/b?project=%s", c.baseURL(), config.GCS.ProjectID)
	req, err := http.NewRequest(http.MethodPost, url, strings.NewReader(string(body)))
	if err != nil {
		return nil, err
	}

	req.Header.Set("Content-Type", "application/json")

	resp, err := c.httpClient.Do(req)
	if err != nil {
		config.Logger.Error(err)
		return nil, err
	}
	defer resp.Body.Close()

	if resp.StatusCode == http.StatusConflict {
		return nil, nil
	}

	if resp.StatusCode >= 400 {
		respBody, _ := io.ReadAll(resp.Body)
		return nil, fmt.Errorf("GCS create bucket %s returned %d: %s", bucketName, resp.StatusCode, string(respBody))
	}

	creds, err := c.createHMACKey()
	if err != nil {
		return nil, fmt.Errorf("create HMAC key: %w", err)
	}

	return creds, nil
}

func (c *Client) UpdateBucket(dto *models.InstanceDTO) (*HMACCredentials, error) {
	if dto.Customizations.Logs.Enabled {
		return c.CreateBucket(dto)
	}

	return nil, c.DeleteBucket(dto.Instance)
}

func (c *Client) DeleteBucket(instance string) error {
	bucketName := bucketPrefix + instance

	if err := c.emptyBucket(bucketName); err != nil {
		return fmt.Errorf("empty bucket %s: %w", bucketName, err)
	}

	url := fmt.Sprintf("%s/b/%s", c.baseURL(), bucketName)
	req, err := http.NewRequest(http.MethodDelete, url, nil)
	if err != nil {
		return err
	}

	resp, err := c.httpClient.Do(req)
	if err != nil {
		config.Logger.Error(err)
		return err
	}
	defer resp.Body.Close()

	if resp.StatusCode == http.StatusNotFound {
		return nil
	}

	if resp.StatusCode >= 400 {
		respBody, _ := io.ReadAll(resp.Body)
		return fmt.Errorf("GCS delete bucket %s returned %d: %s", bucketName, resp.StatusCode, string(respBody))
	}

	return nil
}

func (c *Client) Health() error {
	url := fmt.Sprintf("%s/b?project=%s&maxResults=1", c.baseURL(), config.GCS.ProjectID)
	req, err := http.NewRequest(http.MethodGet, url, nil)
	if err != nil {
		return err
	}

	resp, err := c.httpClient.Do(req)
	if err != nil {
		return err
	}
	defer resp.Body.Close()

	if resp.StatusCode >= 400 {
		respBody, _ := io.ReadAll(resp.Body)
		return fmt.Errorf("GCS health check returned %d: %s", resp.StatusCode, string(respBody))
	}

	return nil
}

func (c *Client) emptyBucket(bucketName string) error {
	url := fmt.Sprintf("%s/b/%s/o", c.baseURL(), bucketName)
	req, err := http.NewRequest(http.MethodGet, url, nil)
	if err != nil {
		return err
	}

	resp, err := c.httpClient.Do(req)
	if err != nil {
		return err
	}
	defer resp.Body.Close()

	if resp.StatusCode == http.StatusNotFound {
		return nil
	}

	if resp.StatusCode >= 400 {
		respBody, _ := io.ReadAll(resp.Body)
		return fmt.Errorf("GCS list objects returned %d: %s", resp.StatusCode, string(respBody))
	}

	var result struct {
		Items []struct {
			Name string `json:"name"`
		} `json:"items"`
	}
	if err := json.NewDecoder(resp.Body).Decode(&result); err != nil {
		return nil
	}

	for _, item := range result.Items {
		delURL := fmt.Sprintf("%s/b/%s/o/%s", c.baseURL(), bucketName, item.Name)
		delReq, err := http.NewRequest(http.MethodDelete, delURL, nil)
		if err != nil {
			return err
		}

		delResp, err := c.httpClient.Do(delReq)
		if err != nil {
			return err
		}
		delResp.Body.Close()

		if delResp.StatusCode >= 400 && delResp.StatusCode != http.StatusNotFound {
			return fmt.Errorf("GCS delete object %s returned %d", item.Name, delResp.StatusCode)
		}
	}

	return nil
}

func (c *Client) DeleteHMACKey(accessKeyId string) error {
	if accessKeyId == "" {
		return nil
	}

	// Deactivate the key first
	deactivateURL := fmt.Sprintf("%s/projects/%s/hmacKeys/%s", c.baseURL(), config.GCS.ProjectID, accessKeyId)
	deactivateBody, _ := json.Marshal(map[string]string{"state": "INACTIVE"})
	req, err := http.NewRequest(http.MethodPut, deactivateURL, strings.NewReader(string(deactivateBody)))
	if err != nil {
		return err
	}
	req.Header.Set("Content-Type", "application/json")

	resp, err := c.httpClient.Do(req)
	if err != nil {
		return fmt.Errorf("deactivate HMAC key %s: %w", accessKeyId, err)
	}
	resp.Body.Close()

	if resp.StatusCode == http.StatusNotFound {
		return nil
	}

	if resp.StatusCode >= 400 {
		return fmt.Errorf("deactivate HMAC key %s returned %d", accessKeyId, resp.StatusCode)
	}

	// Delete the key
	deleteURL := fmt.Sprintf("%s/projects/%s/hmacKeys/%s", c.baseURL(), config.GCS.ProjectID, accessKeyId)
	req, err = http.NewRequest(http.MethodDelete, deleteURL, nil)
	if err != nil {
		return err
	}

	resp, err = c.httpClient.Do(req)
	if err != nil {
		return fmt.Errorf("delete HMAC key %s: %w", accessKeyId, err)
	}
	resp.Body.Close()

	if resp.StatusCode == http.StatusNotFound {
		return nil
	}

	if resp.StatusCode >= 400 {
		return fmt.Errorf("delete HMAC key %s returned %d", accessKeyId, resp.StatusCode)
	}

	return nil
}

func (c *Client) createHMACKey() (*HMACCredentials, error) {
	url := fmt.Sprintf("%s/projects/%s/hmacKeys?serviceAccountEmail=%s",
		c.baseURL(), config.GCS.ProjectID, config.GCS.ServiceAccountEmail)

	req, err := http.NewRequest(http.MethodPost, url, nil)
	if err != nil {
		return nil, err
	}

	resp, err := c.httpClient.Do(req)
	if err != nil {
		return nil, err
	}
	defer resp.Body.Close()

	if resp.StatusCode >= 400 {
		respBody, _ := io.ReadAll(resp.Body)
		return nil, fmt.Errorf("GCS create HMAC key returned %d: %s", resp.StatusCode, string(respBody))
	}

	var result struct {
		Metadata struct {
			AccessId string `json:"accessId"`
		} `json:"metadata"`
		Secret string `json:"secret"`
	}
	if err := json.NewDecoder(resp.Body).Decode(&result); err != nil {
		return nil, fmt.Errorf("decode HMAC key response: %w", err)
	}

	return &HMACCredentials{
		AccessKey: result.Metadata.AccessId,
		SecretKey: result.Secret,
	}, nil
}

func (c *Client) baseURL() string {
	if config.GCS.Endpoint != "" {
		return strings.TrimRight(config.GCS.Endpoint, "/")
	}

	return "https://storage.googleapis.com/storage/v1"
}
