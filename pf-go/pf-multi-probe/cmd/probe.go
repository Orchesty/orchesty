package main

import (
	"pf-go/pf-multi-probe/pkg/probe"
	"github.com/go-redis/redis"
	"os"
	"strconv"
)

func main() {
	db, _ := strconv.Atoi(os.Getenv("REDIS_DB"))
	r := redis.NewClient(&redis.Options{
		Addr:     os.Getenv("REDIS_HOST") + ":" + os.Getenv("REDIS_PORT"),
		Password: os.Getenv("REDIS_PASS"),
		DB:       db,
	})

	srv := probe.Server{Redis: r}
	srv.Start()
}
