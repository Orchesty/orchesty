package config

import (
	"fmt"
	"time"

	"github.com/jinzhu/configor"

	log "github.com/sirupsen/logrus"
)

const (
	OUTPUT_MONGO  = "mongo"
	OUTPUT_INFLUX = "influx"
)

type (
	rabbitMq struct {
		Host     string `default:"http://rabbitmq:15672/" env:"RABBIT_HOST"`
		Username string `default:"guest" env:"RABBIT_USERNAME"`
		Password string `default:"guest" env:"RABBIT_PASSWORD"`
	}

	mongoDb struct {
		Collection string `default:"rabbitmq" env:"MONGO_COLLECTION"`
		DSN        string `default:"" env:"MONGO_DSN"`
	}

	influxDb struct {
		Host        string `default:"kapacitor" env:"METRICS_HOST"`
		Port        int    `default:"9092" env:"METRICS_PORT"`
		Database    string `default:"pipes" env:"INFLUX_DATABASE"`
		Retention   string `default:"default" env:"INFLUX_RETENTION"`
		Measurement string `default:"rabbitmq_queue" env:"INFLUX_MEASUREMENT"`

		DSN string
	}

	app struct {
		Debug  bool          `default:"false" env:"APP_DEBUG"`
		Tick   time.Duration `default:"5" env:"TICK"` // in seconds, must be same as METRICS_RABBIT_INTERVAL in pf-bundles for correct avg calculations
		Output string        `default:"influx" env:"METRICS_SERVICE"`
	}

	config struct {
		App      *app
		RabbitMq *rabbitMq
		Mongo    *mongoDb
		Influx   *influxDb
	}
)

var (
	App      app
	MongoDb  mongoDb
	RabbitMQ rabbitMq
	InfluxDb influxDb

	c = config{
		App:      &App,
		RabbitMq: &RabbitMQ,
		Mongo:    &MongoDb,
		Influx:   &InfluxDb,
	}
)

func init() {
	log.StandardLogger()
	if err := configor.Load(&c); err != nil {
		log.Fatal(err)
	}

	if App.Output != OUTPUT_INFLUX && App.Output != OUTPUT_MONGO {
		log.Fatalf("invalid output [type=%s], allowed [%s, %s]", App.Output, OUTPUT_INFLUX, OUTPUT_MONGO)
	}

	InfluxDb.DSN = fmt.Sprintf("http://%s:%d", InfluxDb.Host, InfluxDb.Port)

	if App.Debug {
		log.SetLevel(log.DebugLevel)
	} else {
		log.SetLevel(log.InfoLevel)
	}

	App.Tick *= time.Second
}
