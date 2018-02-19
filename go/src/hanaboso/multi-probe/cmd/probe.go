package main

import (
	"hanaboso/multi-probe/pkg/probe"
	"hanaboso/utils/env"
	"log"
	"net/http"
	"os"
	"os/signal"
	"strconv"
	"syscall"
	"time"

	"github.com/go-redis/redis"
)

// main runs the
func main() {
	host := env.GetEnv("REDIS_HOST", "localhost")
	port := env.GetEnv("REDIS_PORT", "6379")
	pass := env.GetEnv("REDIS_PASS", "")
	db, _ := strconv.Atoi(env.GetEnv("REDIS_DB", "0"))

	rCli := redis.NewClient(&redis.Options{
		Addr:     host + ":" + port,
		Password: pass,
		DB:       db,
	})
	storage := probe.RedisStorage{Client: rCli}

	var httpClient = http.Client{Timeout: time.Second * 10}
	var checker = probe.HttpCheck{Client: &httpClient}

	srv := probe.Server{Storage: &storage, CheckerSvc: &checker}
	srv.Start(8007)

	gracefulShutdown(&srv)
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
