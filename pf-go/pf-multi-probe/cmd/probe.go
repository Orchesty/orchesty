package main

import (
	"pf-go/pf-multi-probe/pkg/probe"
	"github.com/go-redis/redis"
	"os"
	"strconv"
)

func main() {
	host := getenv("REDIS_HOST", "localhost")
	port := getenv("REDIS_PORT", "6379")
	pass := getenv("REDIS_PASS", "")
	db, _ := strconv.Atoi(getenv("REDIS_DB", "0"))

	r := redis.NewClient(&redis.Options{
		Addr:     host + ":" + port,
		Password: pass,
		DB:       db,
	})

	srv := probe.Server{Redis: r}
	srv.Start()
}

func getenv(key, fallback string) string {
	value := os.Getenv(key)
	if len(value) == 0 {
		return fallback
	}
	return value
}
