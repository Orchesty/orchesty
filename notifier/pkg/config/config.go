package config

import (
	log "github.com/hanaboso/go-log/pkg"
	"github.com/hanaboso/go-log/pkg/zap"
	"github.com/jinzhu/configor"
)

type (
	cfg struct {
		App      *app
		MongoDB  *mongoDb
		RabbitMQ *rabbitMq
		Redis    *redis
		Dispatch *dispatch
		Throttle *throttle
	}

	app struct {
		Debug bool `env:"APP_DEBUG" default:"false"`
	}

	mongoDb struct {
		Dsn string `env:"MONGO_DSN" required:"true"`
	}

	rabbitMq struct {
		Dsn string `env:"RABBITMQ_DSN" default:"amqp://rabbitmq"`
	}

	redis struct {
		URL string `env:"REDIS_DSN" default:""`
	}

	dispatch struct {
		URL     string `env:"STARTING_POINT_URL" default:""`
		Timeout int    `env:"STARTING_POINT_TIMEOUT" default:"30"`
	}

	throttle struct {
		Window              int `env:"THROTTLE_WINDOW" default:"60"`
		BufferWindow        int `env:"BUFFER_WINDOW" default:"60"`
		InAppThrottleWindow int `env:"INAPP_THROTTLE_WINDOW" default:"60"`
		// CloudLimitWindow throttles cloud_limit_threshold notifications per
		// resource (messages/storage) so a tenant approaching its plan ceiling
		// receives at most one warning every two hours rather than once a
		// minute (matches the limits:tick cadence).
		CloudLimitWindow int    `env:"CLOUD_LIMIT_THROTTLE_WINDOW" default:"7200"`
		Mode             string `env:"THROTTLE_MODE" default:"per_topology_per_preset"`
	}
)

var (
	App      app
	MongoDB  mongoDb
	RabbitMQ rabbitMq
	Redis    redis
	Dispatch dispatch
	Throttle throttle
	Logger   log.Logger

	c = cfg{
		App:      &App,
		MongoDB:  &MongoDB,
		RabbitMQ: &RabbitMQ,
		Redis:    &Redis,
		Dispatch: &Dispatch,
		Throttle: &Throttle,
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
