package config

import (
	"github.com/jinzhu/configor"

	log "github.com/sirupsen/logrus"
)

// Config represents application config
var Config = struct {
	App struct {
		Debug bool `env:"APP_DEBUG"`
	}
	Logger  *log.Logger
	MongoDB struct {
		Dsn        string `env:"MONGO_DSN" required:"true"`
		Collection string `env:"MONGO_COLLECTION" required:"true"`
	}
}{}

func load() {
	Config.Logger = log.StandardLogger()

	if err := configor.Load(&Config); err != nil {
		Config.Logger.Fatalf("Unexpected config error: %s", err.Error())
	}

	if Config.App.Debug {
		Config.Logger.SetLevel(log.DebugLevel)
	} else {
		Config.Logger.SetLevel(log.InfoLevel)
	}
}

func init() {
	load()
}
