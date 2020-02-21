package config

import (
	"os"
	"strconv"

	log "github.com/sirupsen/logrus"
)

const (
	// MetricsInflux ...
	MetricsInflux = "influx"
	// MetricsMongo ...
	MetricsMongo = "mongo"
)

// Config represents config
var Config config

type config struct {
	MongoDB        *mongoDb
	RabbitMQ       *rabbitMq
	Cache          *cache
	InfluxDB       *influxDB
	Logger         *log.Logger
	Cleaner        *cleaner
	Limiter        *limiter
	MetricsService string
}

type mongoDb struct {
	Dsn             string
	MetricsDsn      string
	NodeColl        string
	TopologyColl    string
	HumanTaskColl   string
	WebhookColl     string
	Timeout         string
	MeasurementColl string
}

type rabbitMq struct {
	Hostname             string
	Username             string
	Password             string
	Port                 int16
	CounterQueueName     string
	CounterQueueDurable  bool
	DeliveryMode         int16
	QueueDurable         bool
	MaxConcurrentPublish int16
}

type cache struct {
	Expiration string
	CleanUp    string
}

type influxDB struct {
	Hostname    string
	Port        string
	RefreshTime int16
	Measurement string
}

type cleaner struct {
	CleanUp         int16
	CPUPercentLimit int16
}

type limiter struct {
	GoroutineLimit int16
}

func init() {
	l := log.New()
	l.SetLevel(log.WarnLevel)

	if getEnvBool("APP_DEBUG", false) {
		l.SetLevel(log.DebugLevel)
	}

	Config = config{
		MongoDB: &mongoDb{
			Dsn:             getEnv("MONGO_DSN", ""),
			MetricsDsn:      getEnv("MONGO_METRICS_DSN", ""),
			NodeColl:        getEnv("MONGO_NODE_COLL", "Node"),
			TopologyColl:    getEnv("MONGO_TOPOLOGY_COLL", "Topology"),
			HumanTaskColl:   getEnv("MONGO_HUMAN_TASK_COLL", "LongRunningNodeData"),
			WebhookColl:     getEnv("MONGO_WEBHOOK_COLL", "Webhook"),
			Timeout:         getEnv("MONGO_TIMEOUT", "10"),
			MeasurementColl: getEnv("MONGO_MEASUREMENT", "monolith"),
		},
		RabbitMQ: &rabbitMq{
			Hostname:             getEnv("RABBIT_HOSTNAME", "rabbitmq"),
			Username:             getEnv("RABBIT_USERNAME", "guest"),
			Password:             getEnv("RABBIT_PASSWORD", "guest"),
			Port:                 getEnvInt("RABBIT_PORT", 5672),
			CounterQueueName:     getEnv("RABBIT_COUNTER_QUEUE_NAME", "pipes.multi-counter"),
			CounterQueueDurable:  getEnvBool("RABBIT_COUNTER_QUEUE_DURABLE", true),
			DeliveryMode:         getEnvInt("RABBIT_DELIVERY_MODE", 2), // 0 - 1 Transient, 2 - Persistent
			QueueDurable:         getEnvBool("RABBIT_QUEUE_DURABLE", true),
			MaxConcurrentPublish: getEnvInt("RABBIT_CONCURRENT_PUBLISH_RATE", 32767),
		},
		Cache: &cache{
			Expiration: getEnv("CACHE_EXPIRATION", "24"),
			CleanUp:    getEnv("CACHE_CLEAN_UP", "1"),
		},
		InfluxDB: &influxDB{
			Hostname:    getEnv("INFLUX_HOSTNAME", "influxdb"),
			Port:        getEnv("INFLUX_PORT", "8089"),
			RefreshTime: getEnvInt("INFLUX_REFRESH_TIME", 3600),
			Measurement: getEnv("INFLUX_MEASUREMENT", "monolith"),
		},
		Logger: l,
		Cleaner: &cleaner{
			CleanUp:         getEnvInt("APP_CLEANUP_TIME", 5*60),
			CPUPercentLimit: getEnvInt("APP_CLEANUP_PERCENT", 1),
		},
		Limiter: &limiter{
			GoroutineLimit: getEnvInt("GOROUTINE_LIMIT", 2000),
		},
		MetricsService: getEnv("METRICS_SERVICE", MetricsInflux),
	}
}

// GetConfig getting Config, for test purpose
func GetConfig() interface{} {
	return Config
}

func init() {
	if Config.MetricsService != MetricsInflux &&
		Config.MetricsService != MetricsMongo {
		Config.Logger.Fatalf("invalid metrics service [%s], valid options are: [%s, %s]", Config.MetricsService, MetricsInflux, MetricsMongo)
	}
}

func getEnv(key string, defaultValue string) string {
	value := os.Getenv(key)
	if len(value) == 0 {
		return defaultValue
	}
	return value
}

func getEnvBool(key string, defaultValue bool) bool {
	value := os.Getenv(key)
	if len(value) == 0 {
		return defaultValue
	}

	b, err := strconv.ParseBool(value)
	if err != nil {
		b = defaultValue
	}

	return b
}

func getEnvInt(key string, defaultValue int16) int16 {
	value := os.Getenv(key)
	if len(value) == 0 {
		return defaultValue
	}

	i, err := strconv.ParseInt(value, 0, 16)
	if err != nil {
		return defaultValue
	}

	return int16(i)
}
