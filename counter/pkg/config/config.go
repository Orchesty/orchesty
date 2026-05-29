package config

import (
	"fmt"
	"strings"

	log "github.com/hanaboso/go-log/pkg"
	"github.com/hanaboso/go-log/pkg/zap"
	"github.com/jinzhu/configor"
)

type (
	config struct {
		App           *app
		MongoDb       *mongoDb
		RabbitMq      *rabbitMq
		Metrics       *metrics
		StartingPoint *startingPoint
	}

	app struct {
		Debug               bool `env:"APP_DEBUG" default:"false"`
		RunCallbackTopology bool `env:"APP_RUN_CALLBACK_TOPOLOGY" default:"true"`
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
		TopologyCollection   string `env:"MONGODB_TOPOLOGY_COLLECTION" default:"Topology"`
	}

	metrics struct {
		Dsn         string `env:"METRICS_DSN" required:"true"`
		Measurement string `env:"METRICS_MEASUREMENT" default:"pipes_counter"`
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
	Log           log.Logger
	StartingPoint startingPoint
	c             = config{
		App:           &App,
		MongoDb:       &MongoDb,
		RabbitMq:      &RabbitMq,
		Metrics:       &Metrics,
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
