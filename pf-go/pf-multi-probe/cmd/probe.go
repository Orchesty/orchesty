package main

import (
	"pf-go/pf-multi-probe/pkg/probe"
	"github.com/go-redis/redis"
	"os"
	"strconv"
	"time"
	"net/http"
	"os/signal"
	"syscall"
	"log"
)

// main runs the
func main() {
	host := getEnv("REDIS_HOST", "localhost")
	port := getEnv("REDIS_PORT", "6379")
	pass := getEnv("REDIS_PASS", "")
	db, _ := strconv.Atoi(getEnv("REDIS_DB", "0"))

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

	gracefulShutdown(&srv)
}

// getenv returns the ENV variable value or returns the default value if not set
func getEnv(key, fallback string) string {
	value := os.Getenv(key)
	if len(value) == 0 {
		return fallback
	}
	return value
}

// gracefulShutdown handles SIGINT and SIGTERM signal to stop the app gracefully
func gracefulShutdown(probe *probe.Server) {
	sigs := make(chan os.Signal, 1)
	done := make(chan bool, 1)

	signal.Notify(sigs, syscall.SIGINT, syscall.SIGTERM)

	go func() {
		sig := <-sigs
		log.Println()
		log.Println("Signal received: ", sig)

		probe.Stop()

		done <- true
	}()

	<-done
}
