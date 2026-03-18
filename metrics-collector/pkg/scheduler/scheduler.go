package scheduler

import (
	"context"
	"fmt"
	"sync"
	"time"

	"metrics-collector/pkg/config"
	"metrics-collector/pkg/metrics"
	lokicollector "metrics-collector/pkg/metrics/loki"
	mongodbcollector "metrics-collector/pkg/metrics/mongodb"
	"metrics-collector/pkg/storage"
)

type CollectorWithInterval struct {
	Collector metrics.Collector
	Interval  time.Duration
}

type Scheduler struct {
	collectorsWithIntervals []CollectorWithInterval
	repo                    *storage.MongoRepository
	tickers                 []*time.Ticker
	done                    chan struct{}
	wg                      sync.WaitGroup
}

func NewScheduler(
	collectors []metrics.Collector,
	repo *storage.MongoRepository,
) *Scheduler {
	collectorsWithIntervals := make([]CollectorWithInterval, 0, len(collectors))

	for _, collector := range collectors {
		interval := time.Duration(config.App.Tick) * time.Second

		switch collector.Name() {
		case lokicollector.CollectorName:
			interval = time.Duration(config.App.TickLoki) * time.Hour
		case mongodbcollector.CollectorName:
			interval = time.Duration(config.App.TickMongoDB) * time.Minute
		default:
			interval = time.Duration(config.App.Tick) * time.Second
		}

		collectorsWithIntervals = append(collectorsWithIntervals, CollectorWithInterval{
			Collector: collector,
			Interval:  interval,
		})
	}

	return &Scheduler{
		collectorsWithIntervals: collectorsWithIntervals,
		repo:                    repo,
		done:                    make(chan struct{}),
		tickers:                 make([]*time.Ticker, 0, len(collectorsWithIntervals)),
	}
}

func (s *Scheduler) Start(ctx context.Context) error {
	config.Logger.Info("starting metrics scheduler", map[string]interface{}{
		"collectors": len(s.collectorsWithIntervals),
	})

	for _, cw := range s.collectorsWithIntervals {
		s.collectMetric(ctx, cw.Collector)
	}

	for _, cw := range s.collectorsWithIntervals {
		ticker := time.NewTicker(cw.Interval)
		s.tickers = append(s.tickers, ticker)

		s.wg.Add(1)
		go func(collector metrics.Collector, t *time.Ticker) {
			defer s.wg.Done()
			for {
				select {
				case <-t.C:
					s.collectMetric(ctx, collector)
				case <-s.done:
					return
				}
			}
		}(cw.Collector, ticker)

		config.Logger.Info("started collector", map[string]interface{}{
			"name":     cw.Collector.Name(),
			"interval": cw.Interval.String(),
		})
	}

	return nil
}

func (s *Scheduler) Stop() error {
	config.Logger.Info("stopping metrics scheduler", map[string]interface{}{})

	close(s.done)
	for _, ticker := range s.tickers {
		ticker.Stop()
	}

	s.wg.Wait()
	return nil
}

func (s *Scheduler) collectMetric(ctx context.Context, collector metrics.Collector) {
	config.Logger.Info("collecting metrics", map[string]interface{}{
		"collector": collector.Name(),
	})

	collectCtx, cancel := context.WithTimeout(ctx, 130*time.Second)
	defer cancel()

	err := collector.Collect(collectCtx, s.repo)

	if err != nil {
		config.Logger.Warn(
			fmt.Sprintf("collector %s failed", collector.Name()),
			map[string]interface{}{
				"error": err.Error(),
			})
	}
}
