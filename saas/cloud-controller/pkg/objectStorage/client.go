package objectStorage

import (
	"context"
	"encoding/json"
	"fmt"
	"io"
	"net/http"
	"os"
	"strings"
	"time"

	"cloud-controller/pkg/config"
	"cloud-controller/pkg/models"

	"golang.org/x/oauth2"
	"golang.org/x/oauth2/google"
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
	timeout := 15 * time.Second

	// Default unauthenticated client
	httpClient := &http.Client{Timeout: timeout}

	// If credentials file is provided, try to create an OAuth2-enabled client
	if config.GCS.CredentialsFile != "" {
		data, err := os.ReadFile(config.GCS.CredentialsFile)
		if err != nil {
			config.Logger.Error(err)
			return &Client{httpClient: httpClient}
		}

		ctx := context.Background()

		// Try service-account JWT config first
		jwtCfg, err := google.JWTConfigFromJSON(data, "https://www.googleapis.com/auth/devstorage.full_control")
		if err == nil {
			ts := jwtCfg.TokenSource(ctx)
			httpClient = oauth2.NewClient(ctx, ts)
			httpClient.Timeout = timeout
			return &Client{httpClient: httpClient}
		}

		// Fallback to generic credentials parsing
		creds, err := google.CredentialsFromJSON(ctx, data, "https://www.googleapis.com/auth/devstorage.full_control")
		if err != nil {
			config.Logger.Error(err)
			return &Client{httpClient: httpClient}
		}

		httpClient = oauth2.NewClient(ctx, creds.TokenSource)
		httpClient.Timeout = timeout
	}

	return &Client{httpClient: httpClient}
}

func (c *Client) CreateBucket(dto *models.InstanceDTO) (*HMACCredentials, error) {
	bucketName := bucketPrefix + dto.Instance

	payload := map[string]interface{}{
		"name":     bucketName,
		"location": config.GCS.Location,
		"iamConfiguration": map[string]interface{}{
			"uniformBucketLevelAccess": map[string]bool{"enabled": true},
			"publicAccessPrevention":   "enforced",
		},
		"versioning": map[string]bool{
			"enabled": false,
		},
		"softDeletePolicy": map[string]int{
			"retentionDurationSeconds": 0,
		},
	}

	body, err := json.Marshal(payload)
	if err != nil {
		return nil, err
	}

	url := fmt.Sprintf("%s/b?project=%s", config.GCS.S3Endpoint(), config.GCS.ProjectID)
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
		// Bucket already exists; reuse the configured global HMAC if available.
		if creds := c.globalHMACCredentials(); creds != nil {
			return creds, nil
		}

		return nil, nil
	}

	if resp.StatusCode >= 400 {
		respBody, _ := io.ReadAll(resp.Body)
		return nil, fmt.Errorf("GCS create bucket %s returned %d: %s", bucketName, resp.StatusCode, string(respBody))
	}

	if creds := c.globalHMACCredentials(); creds != nil {
		return creds, nil
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

	url := fmt.Sprintf("%s/b/%s", config.GCS.S3Endpoint(), bucketName)
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
	url := fmt.Sprintf("%s/b?project=%s&maxResults=1", config.GCS.S3Endpoint(), config.GCS.ProjectID)
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
	url := fmt.Sprintf("%s/b/%s/o", config.GCS.S3Endpoint(), bucketName)
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
		delURL := fmt.Sprintf("%s/b/%s/o/%s", config.GCS.S3Endpoint(), bucketName, item.Name)
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

	if globalCreds := c.globalHMACCredentials(); globalCreds != nil && accessKeyId == globalCreds.AccessKey {
		return nil
	}

	// Deactivate the key first
	deactivateURL := fmt.Sprintf("%s/projects/%s/hmacKeys/%s", config.GCS.S3Endpoint(), config.GCS.ProjectID, accessKeyId)
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
	deleteURL := fmt.Sprintf("%s/projects/%s/hmacKeys/%s", config.GCS.S3Endpoint(), config.GCS.ProjectID, accessKeyId)
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
		config.GCS.S3Endpoint(), config.GCS.ProjectID, config.GCS.ServiceAccountEmail)

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

func (c *Client) globalHMACCredentials() *HMACCredentials {
	if config.GCS.HMACAccessKey == "" || config.GCS.HMACSecretKey == "" {
		return nil
	}

	return &HMACCredentials{
		AccessKey: config.GCS.HMACAccessKey,
		SecretKey: config.GCS.HMACSecretKey,
	}
}
