package config

import (
	"fmt"
	log "github.com/hanaboso/go-log/pkg"
	"github.com/hanaboso/go-log/pkg/zap"
	"github.com/jinzhu/configor"
	"strings"
)

type (
	config struct {
		App           *app
		MongoDb       *mongoDb
		RabbitMq      *rabbitMq
		Metrics       *metrics
		Logs          *logs
		StartingPoint *startingPoint
	}

	app struct {
		Debug bool `env:"APP_DEBUG" default:"false"`
	}

	rabbitMq struct {
		Dsn      string `env:"RABBITMQ_DSN" required:"true"`
		Prefetch int    `env:"RABBITMQ_PREFETCH" default:"50"`
	}

	mongoDb struct {
		Dsn                  string `env:"MONGODB_DSN" required:"true"`
		CounterCollection    string `env:"MONGODB_COUNTER_COLLECTION" default:"MultiCounter"`
		CounterSubCollection string `env:"MONGODB_COUNTER_SUB_COLLECTION" default:"MultiCounterSubProcess"`
		CounterErrCollection string `env:"MONGODB_COUNTER_ERR_COLLECTION" default:"MultiCounterError"`
		ApiTokenCollection   string `env:"MONGODB_API_TOKEN_COLLECTION" default:"ApiToken"`
	}

	metrics struct {
		Dsn         string `env:"METRICS_DSN" required:"true"`
		Measurement string `env:"METRICS_MEASUREMENT" default:"pipes_counter"`
	}

	logs struct {
		Url string `env:"UDP_LOGGER_URL" required:"true"`
	}

	startingPoint struct {
		Dsn string `env:"STARTING_POINT_DSN" required:"true"`
	}
)

var (
	App           app
	MongoDb       mongoDb
	RabbitMq      rabbitMq
	Metrics       metrics
	Logs          logs
	Log           log.Logger
	StartingPoint startingPoint
	c             = config{
		App:           &App,
		MongoDb:       &MongoDb,
		RabbitMq:      &RabbitMq,
		Metrics:       &Metrics,
		Logs:          &Logs,
		StartingPoint: &StartingPoint,
	}
)

func init() {
	if err := configor.Load(&c); err != nil {
		panic(err)
	}

	Log = zap.NewLogger()
	Log.SetLevel(log.ERROR)

	if App.Debug {
		Log.SetLevel(log.DEBUG)
	}

	if !strings.HasPrefix(StartingPoint.Dsn, "http") {
		StartingPoint.Dsn = fmt.Sprintf("http://%s", StartingPoint.Dsn)
	}
}
