package config

import (
	"os"
	"strconv"
)

type Config struct {
	GRPCAddr        string
	HTTPAddr        string
	RequestTimeout  int64
	MaxRequestBytes int64
}

func Load() Config {
	return Config{
		RequestTimeout:  parseInt64(envOrDefault("REQUEST_TIMEOUT", "30"), 30),
		MaxRequestBytes: parseInt64(envOrDefault("MAX_REQUEST_BODY_MB", "50"), 50) * 1024 * 1024,
	}
}

func envOrDefault(key, fallback string) string {
	if v := os.Getenv(key); v != "" {
		return v
	}
	return fallback
}

func parseInt64(s string, fallback int64) int64 {
	v, err := strconv.ParseInt(s, 10, 64)
	if err != nil || v <= 0 {
		return fallback
	}
	return v
}
