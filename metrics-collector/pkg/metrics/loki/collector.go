package loki

import (
	"context"
	"encoding/json"
	"fmt"
	"io"
	"math/big"
	"net/http"
	"net/url"
	"time"

	"metrics-collector/pkg/config"
	"metrics-collector/pkg/models"
	"metrics-collector/pkg/storage"
	"metrics-collector/pkg/utils"
)

const (
	Query      = `{job="loki.source.kubernetes.pod_logs"}`
	BytesQuery = `sum(bytes_over_time({job="loki.source.kubernetes.pod_logs"}[24h]))`
)

const CollectorName = "Loki"

type Collector struct {
	client *http.Client
}

type lokiResponse struct {
	Status string `json:"status"`
	Data   struct {
		Result []struct {
			Values [][2]interface{} `json:"values"`
			Value  [2]interface{}   `json:"value"`
		} `json:"result"`
	} `json:"data"`
	Error string `json:"error"`
}

func NewCollector() *Collector {
	return &Collector{
		client: &http.Client{Timeout: 120 * time.Second},
	}
}

func (c *Collector) Name() string {
	return CollectorName
}

func (c *Collector) Collect(ctx context.Context, repo *storage.MongoRepository) error {
	metric, err := c.fetchMetrics(ctx)
	if err != nil {
		config.Logger.ErrorWrap("failed to fetch Loki metrics", err)
		return err
	}

	if err := repo.SaveLokiMetric(ctx, metric); err != nil {
		config.Logger.ErrorWrap("failed to save Loki metric", err)
		return err
	}

	if err := c.aggregateMetrics(ctx, repo); err != nil {
		config.Logger.ErrorWrap("failed to aggregate Loki metrics", err)
	}

	config.Logger.Debug("Loki metrics collected", map[string]interface{}{
		"retention_days": metric.RetentionDays,
		"oldest_time":    metric.OldestTimestamp,
		"daily_data_mb":  metric.DailyDataSizeMB,
		"total_data_mb":  metric.TotalDataSizeMB,
	})

	return nil
}

func (c *Collector) fetchMetrics(ctx context.Context) (*models.LokiMetric, error) {
	now := time.Now()

	oneMonthAgo := now.AddDate(0, -1, 0)
	startNs := oneMonthAgo.UnixNano()
	endNs := now.UnixNano()

	params := url.Values{
		"query":     {Query},
		"start":     {fmt.Sprintf("%d", startNs)},
		"end":       {fmt.Sprintf("%d", endNs)},
		"limit":     {"1"},
		"direction": {"forward"},
	}

	endpoint := fmt.Sprintf("%s/loki/api/v1/query_range?%s", config.Loki.URL, params.Encode())

	req, err := http.NewRequestWithContext(ctx, "GET", endpoint, nil)
	if err != nil {
		return nil, fmt.Errorf("failed to create request: %w", err)
	}

	resp, err := c.client.Do(req)
	if err != nil {
		return nil, fmt.Errorf("failed to query Loki: %w", err)
	}
	defer resp.Body.Close()

	body, _ := io.ReadAll(resp.Body)

	if resp.StatusCode != http.StatusOK {
		return nil, fmt.Errorf("unexpected status code %d: %s", resp.StatusCode, string(body))
	}

	var lokiResp lokiResponse
	if err := json.Unmarshal(body, &lokiResp); err != nil {
		return nil, fmt.Errorf("failed to decode response: %w", err)
	}

	if lokiResp.Status != "success" {
		return nil, fmt.Errorf("loki API error: %s", lokiResp.Error)
	}

	metric := &models.LokiMetric{
		Timestamp: now,
	}

	if len(lokiResp.Data.Result) == 0 || len(lokiResp.Data.Result[0].Values) == 0 {
		metric.OldestTimestamp = now
		metric.RetentionDays = 0
	} else {
		oldestNsStr := fmt.Sprintf("%v", lokiResp.Data.Result[0].Values[0][0])
		oldestNs := new(big.Int)
		oldestNs.SetString(oldestNsStr, 10)

		oldestMs := new(big.Int)
		oldestMs.Div(oldestNs, big.NewInt(1e6))

		oldestDateTime := time.UnixMilli(oldestMs.Int64())
		retentionMs := now.Sub(oldestDateTime).Milliseconds()
		retentionDays := int(retentionMs / (24 * 60 * 60 * 1000))

		metric.OldestTimestamp = oldestDateTime
		metric.RetentionDays = retentionDays
	}

	dailySizeBytes, err := c.estimateDataSizeFromChunks(ctx)
	if err != nil {
		config.Logger.Warn("failed to fetch daily data size", map[string]interface{}{
			"error": err.Error(),
		})
		dailySizeBytes = 0
	}

	metric.DailyDataSizeMB = utils.RoundFloat(float64(dailySizeBytes)/(1024*1024), 2)
	metric.TotalDataSizeMB = utils.RoundFloat(metric.DailyDataSizeMB*float64(metric.RetentionDays), 2)

	return metric, nil
}

func (c *Collector) estimateDataSizeFromChunks(ctx context.Context) (int64, error) {
	params := url.Values{
		"query": {`sum(bytes_over_time({job="loki.source.kubernetes.pod_logs"}[24h]))`},
	}

	endpoint := fmt.Sprintf("%s/loki/api/v1/query?%s", config.Loki.URL, params.Encode())

	req, err := http.NewRequestWithContext(ctx, "GET", endpoint, nil)
	if err != nil {
		return 0, fmt.Errorf("failed to create request: %w", err)
	}

	resp, err := c.client.Do(req)
	if err != nil {
		return 0, fmt.Errorf("failed to query Loki: %w", err)
	}
	defer resp.Body.Close()

	body, _ := io.ReadAll(resp.Body)

	if resp.StatusCode != http.StatusOK {
		return 0, fmt.Errorf("unexpected status code %d: %s", resp.StatusCode, string(body))
	}

	var lokiResp lokiResponse
	if err := json.Unmarshal(body, &lokiResp); err != nil {
		return 0, fmt.Errorf("failed to decode response: %w", err)
	}

	if lokiResp.Status != "success" {
		return 0, fmt.Errorf("loki API error: %s", lokiResp.Error)
	}

	if len(lokiResp.Data.Result) == 0 || len(lokiResp.Data.Result[0].Value) < 2 {
		return 0, nil
	}

	bytesStr := fmt.Sprintf("%v", lokiResp.Data.Result[0].Value[1])
	var bytes float64
	fmt.Sscanf(bytesStr, "%f", &bytes)

	return int64(bytes / 20.0), nil
}

func (c *Collector) aggregateMetrics(ctx context.Context, repo *storage.MongoRepository) error {
	now := time.Now()
	metrics, err := repo.GetLokiMetricsForMonth(ctx)
	if err != nil {
		return fmt.Errorf("failed to get metrics for month: %w", err)
	}

	if len(metrics) == 0 {
		return nil
	}

	var maxRetentionDays int
	var totalDailyDataMB float64
	lastMetric := metrics[len(metrics)-1]

	for _, m := range metrics {
		if m.RetentionDays > maxRetentionDays {
			maxRetentionDays = m.RetentionDays
		}
		totalDailyDataMB += m.DailyDataSizeMB
	}

	avgDailyDataMB := totalDailyDataMB / float64(len(metrics))
	estimatedTotalMB := avgDailyDataMB * float64(maxRetentionDays)

	currentMonth := now.Format("2006-01")

	agg := &models.LokiAggregation{
		Month:            currentMonth,
		MaxRetentionDays: maxRetentionDays,
		OldestTimestamp:  lastMetric.OldestTimestamp,
		AvgDailyDataMB:   utils.RoundFloat(avgDailyDataMB, 2),
		EstimatedTotalMB: utils.RoundFloat(estimatedTotalMB, 2),
		LastUpdated:      now,
	}

	return repo.SaveLokiAggregation(ctx, agg)
}
