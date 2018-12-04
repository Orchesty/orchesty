package config

import (
	"os"
	"strconv"
)

// Config represents config
var Config config

type config struct {
	MongoDB  *mongoDb
	RabbitMQ *rabbitMq
	Cache    *cache
}

type mongoDb struct {
	Hostname     string
	Username     string
	Password     string
	Database     string
	NodeColl     string
	TopologyColl string
	Timeout      string
}

type rabbitMq struct {
	Hostname            string
	Username            string
	Password            string
	CounterQueueName    string
	CounterQueueDurable bool
	DeliveryMode        int16
}

type cache struct {
	Expiration string
	CleanUp    string
}

func init() {
	Config = config{
		MongoDB: &mongoDb{
			Hostname:     getEnv("MONGO_HOSTNAME", ""),
			Username:     getEnv("MONGO_USERNAME", ""),
			Password:     getEnv("MONGO_PASSWORD", ""),
			Database:     getEnv("MONGO_DATABASE", ""),
			NodeColl:     getEnv("MONGO_NODE_COLL", "Node"),
			TopologyColl: getEnv("MONGO_TOPOLOGY_COLL", "Topology"),
			Timeout:      getEnv("MONGO_TIMEOUT", "10"),
		},
		RabbitMQ: &rabbitMq{
			Hostname:            getEnv("RABBIT_HOSTNAME", "rabbitmq"),
			Username:            getEnv("RABBIT_USERNAME", "guest"),
			Password:            getEnv("RABBIT_PASSWORD", "guest"),
			CounterQueueName:    getEnv("RABBIT_COUNTER_QUEUE_NAME", "pipes.multi-counter"),
			CounterQueueDurable: getEnvBool("RABBIT_COUNTER_QUEUE_DURABLE", true),
			DeliveryMode:        getEnvInt("RABBIT_DELIVERY_MODE", 1),
		},
		Cache: &cache{
			Expiration: getEnv("CACHE_EXPIRATION", "24"),
			CleanUp:    getEnv("CACHE_CLEAN_UP", "1"),
		},
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

	b, err := strconv.ParseBool(key)
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

	i, err := strconv.ParseInt(key, 0, 16)
	if err != nil {
		return defaultValue
	}

	return int16(i)
}
