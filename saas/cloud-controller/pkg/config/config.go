package config

import (
	"github.com/hanaboso/go-log/pkg/zap"
	"github.com/jinzhu/configor"

	log "github.com/hanaboso/go-log/pkg"
)

type config struct {
	App      *app
	MongoDB  *mongoDB
	RabbitMQ *rabbitMQ
	K8s      *k8s
	Helm     *helm
	Orchesty *orchesty
}

type app struct {
	Debug bool `default:"false" env:"APP_DEBUG"`
	Port  int  `default:"8080" env:"APP_PORT"`
}

type mongoDB struct {
	DSN      string `env:"MONGODB_DSN" required:"true"`
	Hostname string `env:"MONGODB_HOSTNAME" required:"true"`
}

type rabbitMQ struct {
	Hostname       string `env:"RABBIT_HOSTNAME" required:"true"`
	ManagementPort string `env:"RABBIT_MANAGEMENT_PORT" default:"15672"`
	AdminUser      string `env:"RABBIT_ADMIN_USER" default:"guest"`
	AdminPass      string `env:"RABBIT_ADMIN_PASS" default:"guest"`
}

type k8s struct {
	ClusterConfig string `env:"K8S_CLUSTER_CONFIG" default:""`
}

type orchesty struct {
	Version string `env:"APP_ORCHESTY_VERSION" default:"2.1"`
}

type helm struct {
	RootDirForFiles string `env:"HELM_ROOT_DIR_FOR_FILES" default:"/tmp/helm"`
	OrchestyVersion string `env:"HELM_ORCHESTY_VERSION" default:"~2.1.15"`
	BridgePoolKey   string `env:"HELM_BRIDGEPOOL_KEY" default:"bridgepool"`
}

var (
	App      app
	MongoDB  mongoDB
	RabbitMQ rabbitMQ
	K8s      k8s
	Helm     helm
	Orchesty orchesty
	Logger   log.Logger

	c = config{
		App:      &App,
		MongoDB:  &MongoDB,
		RabbitMQ: &RabbitMQ,
		K8s:      &K8s,
		Helm:     &Helm,
		Orchesty: &Orchesty,
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
