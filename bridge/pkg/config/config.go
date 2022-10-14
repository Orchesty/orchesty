package config

import (
	"fmt"
	"github.com/hanaboso/pipes/bridge/pkg/enum"
	"github.com/hanaboso/pipes/bridge/pkg/logger"
	"github.com/hanaboso/pipes/bridge/pkg/utils/stringx"
	"runtime/debug"
	"strings"

	"github.com/hanaboso/pipes/bridge/pkg/utils/intx"
	"github.com/jinzhu/configor"
	"github.com/rs/zerolog"
	"github.com/rs/zerolog/log"
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
		Dsn         string `env:"METRICS_DSN"`
		Measurement string `env:"NODE_MEASUREMENT" default:"pipes_node"`
	}

	app struct {
		Debug        bool   `env:"APP_DEBUG" default:"false"`
		TopologyJSON string `env:"TOPOLOGY_JSON" default:"/srv/app/topology/topology.json"`
	}

	logs struct {
		Url string `env:"UDP_LOGGER_URL" default:"logstash:5120"`
	}

	startingPoint struct {
		Dsn    string `env:"STARTING_POINT_DSN" default:"http://starting-point:8080"`
		ApiKey string `env:"ORCHESTY_API_KEY" required:"false" default:""`
	}
)

var (
	App           app
	MongoDb       mongoDb
	Metrics       metrics
	Logs          logs
	StartingPoint startingPoint

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

	log.Logger = zerolog.
		New(logger.NewUdpSender(Logs.Url)).
		With().
		Timestamp().
		Stack().
		Str(enum.LogHeader_Service, "bridge").
		Logger()

	zerolog.SetGlobalLevel(zerolog.InfoLevel)
	zerolog.TimeFieldFormat = zerolog.TimeFormatUnix
	zerolog.TimestampFieldName = enum.LogHeader_Timestamp
	zerolog.ErrorFieldName = enum.LogHeader_Message
	zerolog.ErrorStackFieldName = enum.LogHeader_Trace

	if App.Debug {
		zerolog.SetGlobalLevel(zerolog.DebugLevel)
		zerolog.ErrorStackMarshaler = func(err error) interface{} {
			return parseTrace(debug.Stack())
		}
	}

	if !strings.HasPrefix(StartingPoint.Dsn, "http") {
		StartingPoint.Dsn = fmt.Sprintf("http://%s", StartingPoint.Dsn)
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
