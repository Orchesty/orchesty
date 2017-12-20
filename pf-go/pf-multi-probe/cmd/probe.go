package main

import (
	"pf-go/pf-multi-probe/pkg/probe"
	"github.com/go-redis/redis"
	"os"
	"strconv"
	"time"
	"net/http"
)

// main runs the
func main() {
	host := getenv("REDIS_HOST", "localhost")
	port := getenv("REDIS_PORT", "6379")
	pass := getenv("REDIS_PASS", "")
	db, _ := strconv.Atoi(getenv("REDIS_DB", "0"))

	rCli := redis.NewClient(&redis.Options{
		Addr:     host + ":" + port,
		Password: pass,
		DB:       db,
	})
	storage := probe.RedisStorage{Client: rCli}

	var httpClient = http.Client{Timeout: time.Second * 10}
	var checker = probe.HttpChecker{Client: &httpClient}

	srv := probe.Server{Storage: &storage, CheckerSvc: &checker}
	srv.Start(8007)
}

// getenv returns the ENV variable value or returns the default value if not set
func getenv(key, fallback string) string {
	value := os.Getenv(key)
	if len(value) == 0 {
		return fallback
	}
	return value
}
