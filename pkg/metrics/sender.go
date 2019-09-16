package metrics

import (
	"starting-point/pkg/config"
)

// Sender ...
type Sender interface {
	SendMetrics(tags map[string]interface{}, fields map[string]interface{})
}

// NewSender ...
func NewSender() Sender {
	switch config.Config.MetricsService {
	case config.MetricsInflux:
		return newInfluxSender()
	case config.MetricsMongo:
		return newMongoSender()
	}

	config.Config.Logger.Fatalf("not allowed sender service of [%s]", config.Config.MetricsService)
	return nil
}
