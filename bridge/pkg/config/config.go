package config

import (
	"fmt"
	"strings"

	"github.com/hanaboso/go-log/pkg"
	"github.com/hanaboso/go-log/pkg/zap"
	"github.com/jinzhu/configor"
)

type (
	config struct {
		App           *app
		MongoDb       *mongoDb
		Metrics       *metrics
		StartingPoint *startingPoint
	}

	mongoDb struct {
		Dsn                   string `env:"MONGODB_DSN" required:"true"`
		UserTaskCollection    string `env:"USER_TASK_COLLECTION" default:"UserTask"`
		AuditEntityCollection string `env:"AUDIT_ENTITY_COLLECTION" default:"AuditEntity"`
		AuditDataCollection   string `env:"AUDIT_DATA_COLLECTION" default:"AuditData"`
	}

	metrics struct {
		Dsn         string `env:"METRICS_DSN" required:"true"`
		Measurement string `env:"NODE_MEASUREMENT" default:"pipes_node"`
	}

	app struct {
		Debug             bool   `env:"APP_DEBUG" default:"false"`
		TopologyJSON      string `env:"TOPOLOGY_JSON" default:"/srv/app/topology/topology.json"`
		WorkerMaxFailures int    `env:"WORKER_MAX_FAILURES" default:"10"`
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
	StartingPoint startingPoint
	Logger        pkg.Logger

	c = config{
		App:           &App,
		MongoDb:       &MongoDb,
		Metrics:       &Metrics,
		StartingPoint: &StartingPoint,
	}
)

func init() {
	if err := configor.Load(&c); err != nil {
		panic(err)
	}
	Logger = zap.NewLogger()

	if App.Debug {
		Logger.SetLevel(pkg.DEBUG)
	}

	if !strings.HasPrefix(StartingPoint.Dsn, "http") {
		StartingPoint.Dsn = fmt.Sprintf("http://%s", StartingPoint.Dsn)
	}
}
