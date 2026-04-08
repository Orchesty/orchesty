package config

import (
	log "github.com/hanaboso/go-log/pkg"
	"github.com/hanaboso/go-log/pkg/zap"
	"github.com/jinzhu/configor"
)

type (
	cfg struct {
		App     *app
		Backend *backend
	}

	app struct {
		Debug bool `env:"APP_DEBUG" default:"false"`
	}

	backend struct {
		URL     string `env:"BACKEND_URL" required:"true"`
		Timeout int    `env:"BACKEND_TIMEOUT" default:"30"`
	}
)

var (
	App     app
	Backend backend
	Logger  log.Logger

	c = cfg{
		App:     &App,
		Backend: &Backend,
	}
)

func init() {
	if err := configor.Load(&c); err != nil {
		panic(err)
	}

	Logger = zap.NewLogger()
	Logger.SetLevel(log.ERROR)

	if App.Debug {
		Logger.SetLevel(log.DEBUG)
	}
}
