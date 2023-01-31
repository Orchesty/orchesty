package config

import (
	log "github.com/hanaboso/go-log/pkg"
	"github.com/hanaboso/go-log/pkg/zap"
	"github.com/jinzhu/configor"
)

type (
	config struct {
		MongoDB  *mongoDb
		App      *app
		RabbitMQ *rabbitMq
		Metrics  *metrics
		Cache    *cache
		Cleaner  *cleaner
		Limiter  *limiter
	}

	app struct {
		Debug bool `env:"APP_DEBUG" default:"false"`
	}

	mongoDb struct {
		Dsn          string `env:"MONGO_DSN" default:""`
		ApiTokenColl string
		NodeColl     string `env:"MONGO_NODE_COLL" default:"Node"`
		TopologyColl string `env:"MONGO_TOPOLOGY_COLL" default:"Topology"`
		WebhookColl  string `env:"MONGO_WEBHOOK_COLL" default:"Webhook"`
	}
	rabbitMq struct {
		Hostname     string `env:"RABBIT_HOSTNAME" default:"rabbitmq"`
		Username     string `env:"RABBIT_USERNAME" default:"guest"`
		Password     string `env:"RABBIT_PASSWORD" default:"guest"`
		Port         int16  `env:"RABBIT_PORT" default:"5672"`
		Vhost        string `env:"RABBIT_VHOST" default:""`
		DeliveryMode int16  `env:"RABBIT_DELIVERY_MODE" default:"2"`
	}
	cache struct {
		Expiration string `env:"CACHE_EXPIRATION" default:"24"`
		CleanUp    string `env:"CACHE_CLEAN_UP" default:"1"`
	}
	metrics struct {
		Dsn         string `env:"METRICS_DSN" default:""`
		Measurement string `env:"METRICS_MEASUREMENT" default:"monolith"`
	}
	cleaner struct {
		CleanUp         int16 `env:"APP_CLEANUP_TIME" default:"300"`
		CPUPercentLimit int16 `env:"APP_CLEANUP_PERCENT" default:"1"`
	}
	limiter struct {
		GoroutineLimit int16 `env:"GOROUTINE_LIMIT" default:"2000"`
	}
)

var (
	MongoDB  mongoDb
	App      app
	RabbitMQ rabbitMq
	Metrics  metrics
	Cache    cache
	Cleaner  cleaner
	Limiter  limiter
	Logger   log.Logger

	c = config{
		MongoDB:  &MongoDB,
		App:      &App,
		RabbitMQ: &RabbitMQ,
		Metrics:  &Metrics,
		Cache:    &Cache,
		Cleaner:  &Cleaner,
		Limiter:  &Limiter,
	}
)

func init() {
	if err := configor.Load(&c); err != nil {
		panic(err)
	}

	Logger = zap.NewLogger()
	Logger.SetLevel(log.ERROR)

	if App.Debug {
		Logger.SetLevel(log.DEBUG)
	}
}
