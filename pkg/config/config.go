package config

import (
	"github.com/hanaboso/pipes/counter/pkg/logger"
	"github.com/hanaboso/pipes/counter/pkg/utils/intx"
	"github.com/hanaboso/pipes/counter/pkg/utils/stringx"
	"runtime/debug"
	"strings"

	"github.com/jinzhu/configor"
	"github.com/rs/zerolog"
	"github.com/rs/zerolog/log"
)

type (
	config struct {
		App      *app
		MongoDb  *mongoDb
		RabbitMq *rabbitMq
		Metrics  *metrics
	}

	app struct {
		Debug bool `env:"APP_DEBUG" default:"false"`
	}

	rabbitMq struct {
		Dsn      string `env:"RABBITMQ_DSN" required:"true"`
		Prefetch int    `env:"RABBITMQ_PREFETCH" default:"50"`
	}

	mongoDb struct {
		Dsn               string `env:"MONGODB_DSN" required:"true"`
		CounterCollection string `env:"MONGODB_COUNTER_COLLECTION" default:"MultiCounter"`
	}

	metrics struct {
		Dsn         string `env:"METRICS_DSN" required:"true"`
		Measurement string `env:"METRICS_MEASUREMENT" default:"pipes_counter"`
	}
)

var (
	App      app
	MongoDb  mongoDb
	RabbitMq rabbitMq
	Metrics  metrics

	c = config{
		App:      &App,
		MongoDb:  &MongoDb,
		RabbitMq: &RabbitMq,
		Metrics:  &Metrics,
	}
)

func init() {
	log.Logger = zerolog.
		New(logger.NewUdpSender()).
		With().
		Timestamp().
		Stack().
		Str("service", "repeater").
		Logger()

	zerolog.SetGlobalLevel(zerolog.InfoLevel)
	zerolog.TimeFieldFormat = zerolog.TimeFormatUnix
	zerolog.TimestampFieldName = "timestamp"
	zerolog.ErrorFieldName = "message"
	zerolog.ErrorStackFieldName = "trace"

	if err := configor.Load(&c); err != nil {
		log.Fatal().Err(err).Send()
	}

	if App.Debug {
		zerolog.SetGlobalLevel(zerolog.DebugLevel)
		zerolog.ErrorStackMarshaler = func(err error) interface{} {
			return parseTrace(debug.Stack())
		}
	}
}

func parseTrace(trace []byte) []interface{} {
	type frame struct {
		Function string `json:"function"`
		File     string `json:"file"`
	}

	stack := make([]interface{}, 0)
	data := string(trace)

	index := strings.Index(data, ":")
	if index < 0 {
		return nil
	}
	data = data[index+2:]

	lines := strings.Split(data, "\n")
	_ = lines

	limit := intx.Min(len(lines)-1, 16)
	for i := 6; i < limit; i += 2 {
		if strings.Contains(lines[i+1], "/rs/zerolog@") {
			continue
		}

		fns := strings.Split(strings.TrimLeft(lines[i], "\t"), "/")
		fn := fns[len(fns)-1]
		index = strings.LastIndex(fn, "(")
		if index < 0 {
			continue
		}
		fn = fn[:index]

		fileLine := strings.TrimLeft(lines[i+1], "\t")

		stack = append(stack, frame{
			File:     stringx.ToChar(fileLine, " "),
			Function: fn,
		})
	}

	return stack
}
