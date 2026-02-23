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
	"metrics-collector/pkg/models"
	"metrics-collector/pkg/storage"
	"metrics-collector/pkg/utils"
)

type Collector struct {
	client *http.Client
}

type queueInfo struct {
	Messages               int64 `json:"messages"`
	MessageBytesPersistent int64 `json:"message_bytes_persistent"`
	MessageBytesRAM        int64 `json:"message_bytes_ram"`
}

func NewCollector() *Collector {
	return &Collector{
		client: &http.Client{Timeout: 10 * time.Second},
	}
}

func (c *Collector) Name() string {
	return "RabbitMQ"
}

func (c *Collector) Collect(ctx context.Context, repo *storage.MongoRepository) error {
	metric, err := c.fetchMetrics(ctx)
	if err != nil {
		config.Logger.ErrorWrap("failed to fetch RabbitMQ metrics", err)
		return err
	}

	if err := repo.SaveRabbitMQMetric(ctx, metric); err != nil {
		config.Logger.ErrorWrap("failed to save RabbitMQ metric", err)
		return err
	}

	if err := c.aggregateMetrics(ctx, repo); err != nil {
		config.Logger.ErrorWrap("failed to aggregate RabbitMQ metrics", err)
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
	endpoint := fmt.Sprintf("%s/api/queues/%s", config.RabbitMQ.Url, vHostEncoded)

	req, err := http.NewRequestWithContext(ctx, "GET", endpoint, nil)
	if err != nil {
		return nil, fmt.Errorf("failed to create request: %w", err)
	}

	auth := base64.StdEncoding.EncodeToString([]byte(config.RabbitMQ.User + ":" + config.RabbitMQ.Password))
	req.Header.Add("Authorization", "Basic "+auth)

	resp, err := c.client.Do(req)
	if err != nil {
		return nil, fmt.Errorf("failed to fetch queues: %w", err)
	}
	defer resp.Body.Close()

	if resp.StatusCode != http.StatusOK {
		body, _ := io.ReadAll(resp.Body)
		return nil, fmt.Errorf("unexpected status code %d: %s", resp.StatusCode, string(body))
	}

	var queues []queueInfo
	if err := json.NewDecoder(resp.Body).Decode(&queues); err != nil {
		return nil, fmt.Errorf("failed to decode response: %w", err)
	}

	var totalMessages int64
	var totalDiskMB, totalRamMB float64

	for _, q := range queues {
		totalMessages += q.Messages
		totalDiskMB += float64(q.MessageBytesPersistent) / (1024 * 1024)
		totalRamMB += float64(q.MessageBytesRAM) / (1024 * 1024)
	}

	return &models.RabbitMQMetric{
		TotalMessages: totalMessages,
		TotalDiskMB:   utils.RoundFloat(totalDiskMB, 2),
		TotalRamMB:    utils.RoundFloat(totalRamMB, 2),
		Timestamp:     time.Now(),
	}, nil
}

func (c *Collector) aggregateMetrics(ctx context.Context, repo *storage.MongoRepository) error {
	now := time.Now()
	metrics, err := repo.GetRabbitMQMetricsForMonth(ctx)
	if err != nil {
		return fmt.Errorf("failed to get metrics for month: %w", err)
	}

	if len(metrics) == 0 {
		return nil
	}

	var sumMessages float64
	var maxMessages int64
	var sumDisk, sumRam float64
	var maxDisk, maxRam float64

	for _, m := range metrics {
		sumMessages += float64(m.TotalMessages)
		if m.TotalMessages > maxMessages {
			maxMessages = m.TotalMessages
		}
		sumDisk += m.TotalDiskMB
		if m.TotalDiskMB > maxDisk {
			maxDisk = m.TotalDiskMB
		}
		sumRam += m.TotalRamMB
		if m.TotalRamMB > maxRam {
			maxRam = m.TotalRamMB
		}
	}

	count := float64(len(metrics))
	currentMonth := now.Format("2006-01")

	agg := &models.RabbitAggregation{
		Month:       currentMonth,
		AvgMessages: utils.RoundFloat(sumMessages/count, 0),
		MaxMessages: maxMessages,
		AvgDiskMB:   utils.RoundFloat(sumDisk/count, 2),
		MaxDiskMB:   utils.RoundFloat(maxDisk, 2),
		AvgRamMB:    utils.RoundFloat(sumRam/count, 2),
		MaxRamMB:    utils.RoundFloat(maxRam, 2),
		LastUpdated: now,
	}

	return repo.SaveRabbitAggregation(ctx, agg)
}
