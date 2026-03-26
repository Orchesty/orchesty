package config

import (
	"github.com/hanaboso/go-log/pkg"
	"github.com/hanaboso/go-log/pkg/zap"
	"github.com/jinzhu/configor"
)

type (
	config struct {
		App      *app
		RabbitMq *rabbitMq
		MongoDb  *mongoDb
	}

	rabbitMq struct {
		Dsn string `env:"RABBITMQ_DSN" required:"true"`
	}

	mongoDb struct {
		Dsn                string `env:"MONGO_DSN" required:"true"`
		MessageCollection  string `env:"MONGO_COLLECTION" default:"limiter"`
		UserTaskCollection string `env:"USER_TASK_COLLECTION" default:"UserTask"`
		ApiTokenCollection string `env:"MONGODB_API_TOKEN_COLLECTION" default:"ApiToken"`
	}

	app struct {
		Debug            bool   `env:"APP_DEBUG" default:"false"`
		TcpServerAddress string `env:"LIMITER_ADDR" default:"0.0.0.0:3333"`
		SystemUser       string
	}
)

var (
	App      app
	MongoDb  mongoDb
	RabbitMq rabbitMq
	Logger   pkg.Logger

	c = config{
		App:      &App,
		MongoDb:  &MongoDb,
		RabbitMq: &RabbitMq,
	}
)

func init() {
	if err := configor.Load(&c); err != nil {
		panic(err)
	}
	c.App.SystemUser = "orchesty"

	Logger = zap.NewLogger()

	if App.Debug {
		Logger.SetLevel(pkg.DEBUG)
	}
}
