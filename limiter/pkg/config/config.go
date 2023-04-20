package config

import (
	"github.com/hanaboso/go-log/pkg"
	"github.com/hanaboso/go-log/pkg/zerolog"
	"github.com/jinzhu/configor"
)

type (
	config struct {
		App      *app
		RabbitMq *rabbitMq
		MongoDb  *mongoDb
		Logs     *logs
	}

	rabbitMq struct {
		Dsn string `env:"RABBITMQ_DSN" required:"true"`
	}

	mongoDb struct {
		Dsn                string `env:"MONGO_DSN" required:"true"`
		MessageCollection  string `env:"MONGO_COLLECTION" default:"limiter"`
		ApiTokenCollection string `env:"MONGODB_API_TOKEN_COLLECTION" default:"ApiToken"`
	}

	app struct {
		Debug            bool   `env:"APP_DEBUG" default:"false"`
		TcpServerAddress string `env:"LIMITER_ADDR" default:"0.0.0.0:3333"`
		SystemUser       string
	}

	logs struct {
		Url string `env:"UDP_LOGGER_URL" default:"fluentd:5120"`
	}
)

var (
	App      app
	MongoDb  mongoDb
	RabbitMq rabbitMq
	Logger   pkg.Logger
	Logs     logs

	c = config{
		App:      &App,
		MongoDb:  &MongoDb,
		RabbitMq: &RabbitMq,
		Logs:     &Logs,
	}
)

func init() {
	if err := configor.Load(&c); err != nil {
		panic(err)
	}
	c.App.SystemUser = "orchesty"

	zerolog.NewLogger(zerolog.NewUdpSender(Logs.Url))
	Logger = zerolog.NewLogger(zerolog.Printer{})

	if App.Debug {
		Logger.SetLevel(pkg.DEBUG)
	}
}
