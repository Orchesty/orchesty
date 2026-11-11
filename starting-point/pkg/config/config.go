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
		Sync     *syncRun
	}

	// syncRun configures run-sync endpoints (synchronous call over an async topology)
	syncRun struct {
		// Timeout - how long a run-sync request waits for a result before answering 202
		Timeout int `env:"SYNC_TIMEOUT" default:"25"`
		// ReplyBaseUrl - address of THIS starting-point instance as seen by bridges (reply-to callback)
		ReplyBaseUrl string `env:"SYNC_REPLY_BASE_URL" default:"http://starting-point:8080"`
		// WaitLimit - how many run-sync requests may wait at once; more get 429 (0 = no limit)
		WaitLimit int `env:"SYNC_WAIT_LIMIT" default:"200"`
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
		Dsn          string `env:"RABBITMQ_DSN" default:"amqp://rabbitmq"`
		DeliveryMode int16  `env:"RABBIT_DELIVERY_MODE" default:"2"`
	}
	cache struct {
		Expiration string `env:"CACHE_EXPIRATION" default:"60"`
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
	Sync     syncRun
	Logger   log.Logger

	c = config{
		MongoDB:  &MongoDB,
		App:      &App,
		RabbitMQ: &RabbitMQ,
		Metrics:  &Metrics,
		Cache:    &Cache,
		Cleaner:  &Cleaner,
		Limiter:  &Limiter,
		Sync:     &Sync,
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

	MongoDB.ApiTokenColl = "ApiToken"
}
