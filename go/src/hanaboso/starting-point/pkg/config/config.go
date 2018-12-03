package config

import (
	"os"
)

// Config represents config
var Config config

type config struct {
	MongoDB  *mongoDb
	RabbitMQ *rabbitMq
	Cache    *cache
}

type mongoDb struct {
	Hostname string
	Username string
	Password string
	Database string
	Timeout  string
}

type rabbitMq struct {
	Hostname            string
	Username            string
	Password            string
	CounterQueueName    string
	CounterQueueDurable string
	DeliveryMode        string
}

type cache struct {
	Expiration string
	CleanUp    string
}

func init() {
	Config = config{
		MongoDB: &mongoDb{
			Hostname: getEnv("MONGO_HOSTNAME", ""),
			Username: getEnv("MONGO_USERNAME", ""),
			Password: getEnv("MONGO_PASSWORD", ""),
			Database: getEnv("MONGO_DATABASE", ""),
			Timeout:  getEnv("MONGO_TIMEOUT", "60"),
		},
		RabbitMQ: &rabbitMq{
			Hostname:            getEnv("RABBIT_HOSTNAME", ""),
			Username:            getEnv("RABBIT_USERNAME", ""),
			Password:            getEnv("RABBIT_PASSWORD", ""),
			CounterQueueName:    getEnv("RABBIT_COUNTER_QUEUE_NAME", ""),
			CounterQueueDurable: getEnv("RABBIT_COUNTER_QUEUE_DURABLE", ""),
			DeliveryMode:        getEnv("RABBIT_DELIVERY_MODE", ""),
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
