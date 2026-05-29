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
		App                *app
		MongoDb            *mongoDb
		Metrics            *metrics
		MetricsCollections *metricsCollections
		StartingPoint      *startingPoint
	}

	mongoDb struct {
		Dsn                   string `env:"MONGODB_DSN" required:"true"`
		UserTaskCollection    string `env:"USER_TASK_COLLECTION" default:"UserTask"`
		AuditEntityCollection string `env:"AUDIT_ENTITY_COLLECTION" default:"AuditEntity"`
		AuditDataCollection   string `env:"AUDIT_DATA_COLLECTION" default:"AuditData"`
		LimiterCollection     string `env:"LIMITER_COLLECTION" default:"limiter"`
	}

	metricsCollections struct {
		StorageCollection  string `env:"METRICS_STORAGE_COLLECTION" default:"db_storage_metrics"`
		RabbitmqCollection string `env:"METRICS_RABBITMQ_COLLECTION" default:"rabbitmq_metrics"`
		LokiCollection     string `env:"METRICS_LOKI_COLLECTION" default:"loki_retention_metrics"`
	}

	metrics struct {
		Dsn         string `env:"METRICS_DSN" required:"true"`
		Measurement string `env:"NODE_MEASUREMENT" default:"pipes_node"`
	}

	app struct {
		Debug             bool   `env:"APP_DEBUG" default:"false"`
		TopologyJSON      string `env:"TOPOLOGY_JSON" default:"/srv/app/topology/topology.json"`
		WorkerMaxFailures int    `env:"WORKER_MAX_FAILURES" default:"10"`

		BackendUrl          string `env:"BACKEND_URL" default:""`
		LimitsCheckInterval int    `env:"LIMITS_CHECK_INTERVAL" default:"60"`
	}

	startingPoint struct {
		Dsn    string `env:"STARTING_POINT_DSN" required:"true"`
		ApiKey string `env:"ORCHESTY_API_KEY" required:"false" default:""`
	}
)

var (
	App                app
	MongoDb            mongoDb
	Metrics            metrics
	MetricsCollections metricsCollections
	StartingPoint      startingPoint
	Logger             pkg.Logger

	c = config{
		App:                &App,
		MongoDb:            &MongoDb,
		Metrics:            &Metrics,
		MetricsCollections: &MetricsCollections,
		StartingPoint:      &StartingPoint,
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

	if App.BackendUrl != "" && !strings.HasPrefix(App.BackendUrl, "http") {
		App.BackendUrl = fmt.Sprintf("http://%s", App.BackendUrl)
	}
}
