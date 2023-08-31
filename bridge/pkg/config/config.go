package config

import (
	"fmt"
	"github.com/hanaboso/go-log/pkg"
	"github.com/hanaboso/go-log/pkg/zerolog"
	"github.com/jinzhu/configor"
	"strings"
)

type (
	config struct {
		App           *app
		MongoDb       *mongoDb
		Metrics       *metrics
		Logs          *logs
		StartingPoint *startingPoint
	}

	mongoDb struct {
		Dsn                string `env:"MONGODB_DSN" required:"true"`
		UserTaskCollection string `env:"USER_TASK_COLLECTION" default:"UserTask"`
	}

	metrics struct {
		Dsn         string `env:"METRICS_DSN" required:"true"`
		Measurement string `env:"NODE_MEASUREMENT" default:"pipes_node"`
	}

	app struct {
		Debug        bool   `env:"APP_DEBUG" default:"false"`
		TopologyJSON string `env:"TOPOLOGY_JSON" default:"/srv/app/topology/topology.json"`
	}

	logs struct {
		Url string `env:"UDP_LOGGER_URL" default:"fluentd:5120"`
	}

	startingPoint struct {
		Dsn    string `env:"STARTING_POINT_DSN" required:"true"`
		ApiKey string `env:"ORCHESTY_API_KEY" required:"false" default:""`
	}
)

var (
	App           app
	MongoDb       mongoDb
	Metrics       metrics
	Logs          logs
	StartingPoint startingPoint
	Logger        pkg.Logger

	c = config{
		App:           &App,
		MongoDb:       &MongoDb,
		Metrics:       &Metrics,
		Logs:          &Logs,
		StartingPoint: &StartingPoint,
	}
)

func init() {
	if err := configor.Load(&c); err != nil {
		panic(err)
	}
	Logger = zerolog.NewLogger(zerolog.NewUdpSender(Logs.Url))

	if App.Debug {
		Logger.SetLevel(pkg.DEBUG)
	}

	if !strings.HasPrefix(StartingPoint.Dsn, "http") {
		StartingPoint.Dsn = fmt.Sprintf("http://%s", StartingPoint.Dsn)
	}
}
