package config

import (
	"github.com/hanaboso/go-log/pkg/zap"
	"github.com/jinzhu/configor"

	log "github.com/hanaboso/go-log/pkg"
)

type config struct {
	RabbitMQ   *rabbitMQConfig
	MongoDB    *mongoDBConfig
	Kubernetes *kubernetesConfig
	Loki       *lokiConfig
	App        *app
}

type rabbitMQConfig struct {
	Url      string `env:"RABBITMQ_URL" default:"http://localhost:15672"`
	User     string `env:"RABBITMQ_USER" default:"guest"`
	Password string `env:"RABBITMQ_PASSWORD" default:"guest"`
	VHost    string `env:"RABBITMQ_VHOST" default:"/"`
}

type mongoDBConfig struct {
	Dsn string `env:"MONGODB_DSN" default:"mongodb://localhost:27017/k8smetrics"`
}

type kubernetesConfig struct {
	Namespace     string `env:"K8S_NAMESPACE" default:"default"`
	ClusterConfig string `env:"K8S_CLUSTER_CONFIG" default:""`
	Enabled       bool   `env:"K8S_ENABLED" default:"false"`
}

type lokiConfig struct {
	URL     string `env:"LOKI_URL" default:"http://localhost:3100"`
	Enabled bool   `env:"LOKI_ENABLED" default:"false"`
}

type app struct {
	Debug    bool `default:"false" env:"APP_DEBUG"`
	Tick     int  `default:"60" env:"TICK"`      // in seconds
	TickLoki int  `default:"12" env:"TICK_LOKI"` // in hours
}

var (
	RabbitMQ   rabbitMQConfig
	Mongo      mongoDBConfig
	Kubernetes kubernetesConfig
	Loki       lokiConfig
	App        app
	Logger     log.Logger

	c = config{
		RabbitMQ:   &RabbitMQ,
		MongoDB:    &Mongo,
		Kubernetes: &Kubernetes,
		Loki:       &Loki,
		App:        &App,
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
}
