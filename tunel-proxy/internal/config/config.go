package config

import (
	"os"
	"strconv"
	"time"
)

type Config struct {
	GRPCAddr        string
	HTTPAddr        string
	RequestTimeout  time.Duration
	MaxRequestBytes int64
}

func Load() Config {
	return Config{
		GRPCAddr:        envOrDefault("GRPC_ADDR", ":50051"),
		HTTPAddr:        envOrDefault("HTTP_ADDR", ":8080"),
		RequestTimeout:  parseDuration(envOrDefault("REQUEST_TIMEOUT", "30s")),
		MaxRequestBytes: parseInt64(envOrDefault("MAX_REQUEST_BODY_MB", "50")) * 1024 * 1024,
	}
}

func envOrDefault(key, fallback string) string {
	if v := os.Getenv(key); v != "" {
		return v
	}
	return fallback
}

func parseDuration(s string) time.Duration {
	d, err := time.ParseDuration(s)
	if err != nil {
		return 30 * time.Second
	}
	return d
}

func parseInt64(s string) int64 {
	v, err := strconv.ParseInt(s, 10, 64)
	if err != nil || v <= 0 {
		return 50
	}
	return v
}
