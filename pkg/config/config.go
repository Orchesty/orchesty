package config

import (
	log "github.com/hanaboso/go-log/pkg"
	"github.com/hanaboso/go-log/pkg/zap"
	"github.com/jinzhu/configor"
)

type (
	config struct {
		App      *app
		MongoDb  *mongoDb
		RabbitMq *rabbitMq
		Metrics  *metrics
		Logs     *logs
	}

	app struct {
		Debug bool `env:"APP_DEBUG" default:"false"`
	}

	rabbitMq struct {
		Dsn      string `env:"RABBITMQ_DSN" required:"true"`
		Prefetch int    `env:"RABBITMQ_PREFETCH" default:"50"`
	}

	mongoDb struct {
		Dsn               string `env:"MONGODB_DSN" required:"true"`
		CounterCollection string `env:"MONGODB_COUNTER_COLLECTION" default:"MultiCounter"`
	}

	metrics struct {
		Dsn         string `env:"METRICS_DSN" required:"true"`
		Measurement string `env:"METRICS_MEASUREMENT" default:"pipes_counter"`
	}

	logs struct {
		Url string `env:"UDP_LOGGER_URL" default:"logstash:5120"`
	}
)

var (
	App      app
	MongoDb  mongoDb
	RabbitMq rabbitMq
	Metrics  metrics
	Logs     logs
	Log      log.Logger
	c        = config{
		App:      &App,
		MongoDb:  &MongoDb,
		RabbitMq: &RabbitMq,
		Metrics:  &Metrics,
		Logs:     &Logs,
	}
)

func init() {
	if err := configor.Load(&c); err != nil {
		panic(err)
	}

	Log = zap.NewLogger()
	Log.SetLevel(log.ERROR)

	if App.Debug {
		Log.SetLevel(log.DEBUG)
	}
}
