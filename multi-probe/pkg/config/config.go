package config

import "github.com/jinzhu/configor"

type (
	storage struct {
		Type string `env:"STORAGE" default:"memory"`
	}
	redis struct {
		Host     string `env:"REDIS_HOST" default:"localhost"`
		Port     string `env:"REDIS_PORT" default:"6379"`
		Password string `env:"REDIS_PASS" default:""`
		Db       int    `env:"REDIS_DB" default:"0"`
	}

	conf struct {
		Storage *storage
		Redis   *redis
	}
)

var (
	Storage storage
	Redis   redis
	c       = conf{
		Storage: &Storage,
		Redis:   &Redis,
	}
)

func init() {
	_ = configor.Load(&c)
}
