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
		// InstanceID is the cloud-side UUID of this Orchesty instance (set by
		// the cloud-controller into the K8s secret as orchesty_cloud_instance_id
		// and propagated via env). Notifier attaches it to dispatched payloads
		// so cloud-backend can route notifications to the correct Instance row
		// independently of the in-pipes tenant_id concept.
		InstanceID string `env:"ORCHESTY_CLOUD_INSTANCE_ID" default:""`
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
		// Window is the generic fallback throttle for presets that haven't
		// been explicitly classified in throttleWindowFor (currently none —
		// reserved for future presets). User-facing failure / limit presets
		// route through `EmailWindow` instead.
		Window              int `env:"THROTTLE_WINDOW" default:"7200"`
		BufferWindow        int `env:"BUFFER_WINDOW" default:"60"`
		InAppThrottleWindow int `env:"INAPP_THROTTLE_WINDOW" default:"60"`
		// EmailWindow throttles all user-facing email notifications (topology
		// failures, limit signals, cloud-limit thresholds) so a recipient
		// receives at most one email per `(tenant, preset, topology[, resource])`
		// every two hours. Combined with `BufferWindow` (event aggregation
		// before each flush), each cycle delivers one digest email per pair.
		// Legacy ENV name `CLOUD_LIMIT_THROTTLE_WINDOW` is retained as the
		// preferred override key — semantics now apply to all email presets.
		EmailWindow int    `env:"CLOUD_LIMIT_THROTTLE_WINDOW" default:"7200"`
		Mode        string `env:"THROTTLE_MODE" default:"per_topology_per_preset"`
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
