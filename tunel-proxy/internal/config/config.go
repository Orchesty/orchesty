package config

import (
	"os"
	"time"
)

type Config struct {
	GRPCAddr       string
	HTTPAddr       string
	RequestTimeout time.Duration
}

func Load() Config {
	return Config{
		GRPCAddr:       envOrDefault("GRPC_ADDR", ":50051"),
		HTTPAddr:       envOrDefault("HTTP_ADDR", ":8080"),
		RequestTimeout: parseDuration(envOrDefault("REQUEST_TIMEOUT", "30s")),
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
