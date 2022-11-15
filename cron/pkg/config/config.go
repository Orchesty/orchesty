package config

import (
	"fmt"
	"github.com/hanaboso/go-log/pkg/zap"
	"github.com/jinzhu/configor"
	"strings"

	log "github.com/hanaboso/go-log/pkg"
)

const OrchestyApiKeyHeader = "Orchesty-Api-Key"

type (
	app struct {
		Debug bool `env:"APP_DEBUG" default:"false"`
	}

	mongo struct {
		Dsn                string `env:"MONGO_DSN" required:"true"`
		Collection         string `env:"MONGO_COLLECTION" required:"true"`
		ApiTokenCollection string
	}

	startingPoint struct {
		Dsn     string `env:"STARTING_POINT_DSN" required:"true"`
		Timeout int    `env:"STARTING_POINT_TIMEOUT" default:"30"`
	}

	logger log.Logger

	config struct {
		App           *app
		Mongo         *mongo
		StartingPoint *startingPoint
	}
)

var (
	App           app
	Mongo         mongo
	Logger        logger
	StartingPoint startingPoint

	appConfig = config{
		App:           &App,
		Mongo:         &Mongo,
		StartingPoint: &StartingPoint,
	}
)

func load() {
	Logger = zap.NewLogger()

	if err := configor.Load(&appConfig); err != nil {
		Logger.Fatal(err)
	}

	if App.Debug {
		Logger.SetLevel(log.DEBUG)
	} else {
		Logger.SetLevel(log.INFO)
	}

	if !strings.HasPrefix(StartingPoint.Dsn, "http") {
		StartingPoint.Dsn = fmt.Sprintf("http://%s", StartingPoint.Dsn)
	}

	Mongo.ApiTokenCollection = "ApiToken"
}

func init() {
	load()
}
