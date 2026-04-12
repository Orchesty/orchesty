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
		URLEmail string `env:"STARTING_POINT_URL_EMAIL" default:""`
		URLSlack string `env:"STARTING_POINT_URL_SLACK" default:""`
		URLInApp string `env:"STARTING_POINT_URL_INAPP" default:""`
		Timeout  int    `env:"STARTING_POINT_TIMEOUT" default:"30"`
	}

	throttle struct {
		WindowMs            int    `env:"THROTTLE_WINDOW_MS" default:"60000"`
		BufferWindowMs      int    `env:"BUFFER_WINDOW_MS" default:"60000"`
		InAppThrottleWindowMs int  `env:"INAPP_THROTTLE_WINDOW_MS" default:"60000"`
		Mode                string `env:"THROTTLE_MODE" default:"per_topology_per_preset"`
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

func DispatchURLs() map[string]string {
	urls := make(map[string]string)
	if Dispatch.URLEmail != "" {
		urls["email"] = Dispatch.URLEmail
	}
	if Dispatch.URLSlack != "" {
		urls["slack"] = Dispatch.URLSlack
	}
	if Dispatch.URLInApp != "" {
		urls["in_app"] = Dispatch.URLInApp
	}
	return urls
}
