package config

import (
	"github.com/hanaboso/go-log/pkg/zap"
	"github.com/jinzhu/configor"

	log "github.com/hanaboso/go-log/pkg"
)

type (
	mongoDBType struct {
		Dsn        string `env:"MONGO_DSN" required:"true"`
		Collection string `env:"MONGO_COLLECTION" required:"true"`
	}
	appType struct {
		Debug bool `env:"APP_DEBUG"`
	}
	configType struct {
		App   *appType
		Mongo *mongoDBType
	}
	logger log.Logger
)

var (
	// MongoDB represents MongoDB config
	MongoDB mongoDBType
	// Logger logger
	Logger logger
	app    appType

	config = configType{
		App:   &app,
		Mongo: &MongoDB,
	}
)

func load() {
	Logger = zap.NewLogger()
	if err := configor.Load(&config); err != nil {
		Logger.Fatal(err)
	}

	if app.Debug {
		Logger.SetLevel(log.DEBUG)
	} else {
		Logger.SetLevel(log.INFO)
	}
}

func init() {
	load()
}
