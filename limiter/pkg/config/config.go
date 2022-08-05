package config

import (
	"os"
	"strconv"

	log "github.com/sirupsen/logrus"
)

// Config represents config
var Config config

type config struct {
	MongoDB *mongoDb
	Logger  *log.Logger
}

type mongoDb struct {
	Dsn string
}

func init() {
	l := log.New()
	l.SetLevel(log.WarnLevel)

	if getEnvBool("APP_DEBUG", false) {
		l.SetLevel(log.DebugLevel)
	}

	Config = config{
		MongoDB: &mongoDb{
			Dsn: getEnv("MONGO_DSN", ""),
		},
		Logger: l,
	}
}

// GetConfig getting Config, for test purpose
func GetConfig() interface{} {
	return Config
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
