package config

import (
	"time"

	"github.com/hanaboso/go-log/pkg/zap"
	"github.com/jinzhu/configor"

	log "github.com/hanaboso/go-log/pkg"
)

type (
	rabbitMq struct {
		Host     string `env:"RABBIT_HOST" required:"true"`
		VHost    string `default:"/" env:"RABBIT_VHOST"`
		Username string `default:"guest" env:"RABBIT_USERNAME"`
		Password string `default:"guest" env:"RABBIT_PASSWORD"`
	}

	metrics struct {
		Dsn                  string `env:"METRICS_DSN" required:"true"`
		Measurement          string `default:"rabbitmq" env:"METRICS_MEASUREMENT"`
		ConsumerMeasurement  string `default:"rabbitmq_consumer" env:"CONSUMER_MEASUREMENT"`
		ContainerMeasurement string `default:"container" env:"CONTAINER_MEASUREMENT"`
	}

	mongo struct {
		Dsn          string `env:"MONGO_DSN" required:"true"`
		Node         string `default:"Node" env:"NODE_COLLECTION"`
		MultiCounter string `default:"MultiCounter" env:"MULTI_COUNTER"`
		Limiter      string `default:"limiter" env:"LIMITER"`
		Repeater     string `default:"repeater" env:"REPEATER"`
	}

	app struct {
		Debug          bool          `default:"false" env:"APP_DEBUG"`
		Tick           time.Duration `default:"5" env:"TICK"`             // in seconds, must be same as METRICS_RABBIT_INTERVAL in pf-bundles for correct avg calculations
		MonitoringTick time.Duration `default:"60" env:"MONITORING_TICK"` // in seconds
		MonitorLabel   string        `default:"app.kubernetes.io/instance=pipes" env:"COMPONENTS_DEPLOYMENT_LABEL"`
	}

	generator struct {
		Network string `env:"DOCKER_NETWORK"`
		Mode    string `env:"PLATFORM"`
	}

	config struct {
		App       *app
		RabbitMq  *rabbitMq
		Metrics   *metrics
		Mongo     *mongo
		Generator *generator
	}
)

var (
	App       app
	RabbitMQ  rabbitMq
	Metrics   metrics
	Mongo     mongo
	Generator generator
	Logger    log.Logger

	c = config{
		App:       &App,
		RabbitMq:  &RabbitMQ,
		Metrics:   &Metrics,
		Mongo:     &Mongo,
		Generator: &Generator,
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
	App.MonitoringTick *= time.Second
}
