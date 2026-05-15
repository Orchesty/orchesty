package rabbitmq

import (
	"context"
	"encoding/base64"
	"encoding/json"
	"fmt"
	"io"
	"net/http"
	"net/url"
	"time"

	"metrics-collector/pkg/config"
	"metrics-collector/pkg/metrics"
	"metrics-collector/pkg/models"
	"metrics-collector/pkg/utils"
)

const CollectorName = "RabbitMQ"

type Collector struct {
	client           httpDoer
	lastAggregatedAt time.Time
	authHeader       string
}

type httpDoer interface {
	Do(req *http.Request) (*http.Response, error)
}

type queueInfo struct {
	Name                   string   `json:"name"`
	Messages               int64    `json:"messages"`
	MessageBytesPersistent int64    `json:"message_bytes_persistent"`
	MessageBytesRAM        int64    `json:"message_bytes_ram"`
	Members                []string `json:"members"`
}

type paginatedResponse struct {
	Items      []queueInfo `json:"items"`
	PageCount  int         `json:"page_count"`
	TotalCount int         `json:"total_count"`
}

func NewCollector() *Collector {
	auth := base64.StdEncoding.EncodeToString(
		[]byte(config.RabbitMQ.User + ":" + config.RabbitMQ.Password),
	)

	return &Collector{
		client:     &http.Client{Timeout: 10 * time.Second},
		authHeader: "Basic " + auth,
	}
}

func (c *Collector) Name() string {
	return CollectorName
}

func (c *Collector) Collect(ctx context.Context, repo metrics.Repository) error {
	metric, err := c.fetchMetrics(ctx)
	if err != nil {
		config.Logger.ErrorWrap("failed to fetch RabbitMQ metrics", err)
		return err
	}

	if err := repo.SaveRabbitMQMetric(ctx, metric); err != nil {
		config.Logger.ErrorWrap("failed to save RabbitMQ metric", err)
		return err
	}

	now := time.Now()
	if now.Sub(c.lastAggregatedAt) >= time.Hour {
		if err := c.aggregateMetrics(ctx, repo); err != nil {
			config.Logger.ErrorWrap("failed to aggregate RabbitMQ metrics", err)
		}
		c.lastAggregatedAt = now
	}

	config.Logger.Debug("RabbitMQ metrics collected", map[string]interface{}{
		"messages": metric.TotalMessages,
		"disk_mb":  metric.TotalDiskMB,
		"ram_mb":   metric.TotalRamMB,
	})

	return nil
}

func (c *Collector) fetchMetrics(ctx context.Context) (*models.RabbitMQMetric, error) {
	vHostEncoded := url.QueryEscape(config.RabbitMQ.VHost)
	baseEndpoint := fmt.Sprintf("%s/api/queues/%s", config.RabbitMQ.Url, vHostEncoded)

	page := 1
	pageSize := 100

	var totalMessages int64
	var totalDiskMB, totalRamMB float64

	for {
		endpoint := fmt.Sprintf("%s?page=%d&page_size=%d", baseEndpoint, page, pageSize)

		req, err := http.NewRequestWithContext(ctx, "GET", endpoint, nil)
		if err != nil {
			return nil, fmt.Errorf("failed to create request: %w", err)
		}

		req.Header.Add("Authorization", c.authHeader)

		resp, err := c.client.Do(req)
		if err != nil {
			return nil, fmt.Errorf("failed to fetch queues: %w", err)
		}

		items, pageCount, err := c.parseResponse(resp)
		if err != nil {
			return nil, err
		}

		if len(items) == 0 {
			break
		}

		for _, q := range items {
			// Skip excluded queues
			if c.isQueueExcluded(q.Name) {
				continue
			}

			diskMb := float64(q.MessageBytesPersistent) / (1024 * 1024)
			ramMb := float64(q.MessageBytesRAM) / (1024 * 1024)

			if config.RabbitMQ.HaMode {
				diskMb *= float64(len(q.Members))
				ramMb *= float64(len(q.Members))
			}

			totalMessages += q.Messages
			totalDiskMB += diskMb
			totalRamMB += ramMb
		}

		if page >= pageCount {
			break
		}

		page++
	}

	return &models.RabbitMQMetric{
		TotalMessages: totalMessages,
		TotalDiskMB:   utils.RoundFloat(totalDiskMB, 2),
		TotalRamMB:    utils.RoundFloat(totalRamMB, 2),
		Timestamp:     time.Now(),
	}, nil
}

func (c *Collector) parseResponse(resp *http.Response) ([]queueInfo, int, error) {
	defer resp.Body.Close()

	if resp.StatusCode != http.StatusOK {
		body, _ := io.ReadAll(resp.Body)
		return nil, 0, fmt.Errorf("unexpected status code %d: %s", resp.StatusCode, string(body))
	}

	var paginatedResp paginatedResponse
	if err := json.NewDecoder(resp.Body).Decode(&paginatedResp); err != nil {
		return nil, 0, fmt.Errorf("failed to decode response: %w", err)
	}

	return paginatedResp.Items, paginatedResp.PageCount, nil
}

func (c *Collector) isQueueExcluded(queueName string) bool {
	for _, excluded := range config.RabbitMQ.ExcludedQueues {
		if queueName == excluded {
			return true
		}
	}
	return false
}

func (c *Collector) aggregateMetrics(ctx context.Context, repo metrics.Repository) error {
	agg, err := repo.GetRabbitMQMonthlyAggregation(ctx)
	if err != nil {
		return fmt.Errorf("failed to aggregate metrics in MongoDB: %w", err)
	}
	if agg == nil {
		return nil
	}
	return repo.SaveRabbitAggregation(ctx, agg)
}
