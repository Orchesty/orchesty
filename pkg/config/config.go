package config

import (
	"fmt"
	"github.com/jinzhu/configor"
	"github.com/rs/zerolog"
	"github.com/rs/zerolog/log"
	"os"
	"runtime/debug"
)

type (
	config struct {
		Debug        *bool   `env:"APP_DEBUG" default:"false"`
		TopologyJSON *string `env:"TOPOLOGY_JSON"`
		RabbitMQDSN  *string `env:"RABBITMQ_DSN" required:"true"`
	}
)

var (
	Debug        bool
	TopologyJSON string
	RabbitMQDSN  string

	c = config{
		Debug:        &Debug,
		TopologyJSON: &TopologyJSON,
		RabbitMQDSN:  &RabbitMQDSN,
	}
)

func init() {
	log.Logger = zerolog.
		New(os.Stderr).
		With().
		Timestamp().
		Stack().
		Str("service", "bridge").
		Logger()

	zerolog.TimestampFieldName = "timestamp"
	zerolog.ErrorFieldName = "message"
	zerolog.ErrorStackFieldName = "trace"

	if err := configor.Load(&c); err != nil {
		log.Fatal().Err(err).Msg("")
	}

	if Debug {
		zerolog.SetGlobalLevel(zerolog.DebugLevel)
		// TODO create better trace formatted (something like pkgerrors.MarshalStack which is not supported)
		zerolog.ErrorStackMarshaler = func(err error) interface{} {
			return fmt.Sprintf("%s", debug.Stack()) // TODO remove first caller
		}
	} else {
		zerolog.SetGlobalLevel(zerolog.InfoLevel)
	}
}
