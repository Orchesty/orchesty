package config

import (
	"time"

	"github.com/hanaboso/go-log/pkg/zap"
	"github.com/jinzhu/configor"

	log "github.com/hanaboso/go-log/pkg"
)

type (
	rabbitMq struct {
		Host     string `default:"http://rabbitmq:15672/" env:"RABBIT_HOST"`
		Username string `default:"guest" env:"RABBIT_USERNAME"`
		Password string `default:"guest" env:"RABBIT_PASSWORD"`
	}

	metrics struct {
		Dsn         string `default:"" env:"METRICS_DSN"`
		Measurement string `default:"rabbitmq" env:"METRICS_MEASUREMENT"`
	}

	app struct {
		Debug bool          `default:"false" env:"APP_DEBUG"`
		Tick  time.Duration `default:"5" env:"TICK"` // in seconds, must be same as METRICS_RABBIT_INTERVAL in pf-bundles for correct avg calculations
	}

	config struct {
		App      *app
		RabbitMq *rabbitMq
		Metrics  *metrics
	}
)

var (
	// App settings
	App app
	// RabbitMQ settings
	RabbitMQ rabbitMq
	// Metrics settings
	Metrics metrics
	// Logger logger
	Logger log.Logger

	c = config{
		App:      &App,
		RabbitMq: &RabbitMQ,
		Metrics:  &Metrics,
	}
)

func init() {
	Logger = zap.NewLogger()
	if err := configor.Load(&c); err != nil {
		Logger.Fatal(err)
	}

	if App.Debug {
		Logger.SetLevel(log.DEBUG)
	} else {
		Logger.SetLevel(log.INFO)
	}

	App.Tick *= time.Second
}
