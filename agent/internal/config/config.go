package config

import (
	"fmt"
	"os"
	"strconv"
)

type Config struct {
	ServerURL       string
	APIKey          string
	HostLabel       string
	CollectInterval int
	RetryAttempts   int
	OutputFile      string // Phase 1: local file path, or "stdout"
}

func Load() (*Config, error) {
	cfg := &Config{
		HostLabel:       getEnv("HOST_LABEL", hostname()),
		CollectInterval: getEnvInt("COLLECT_INTERVAL", 30),
		RetryAttempts:   getEnvInt("RETRY_ATTEMPTS", 3),
		OutputFile:      getEnv("OUTPUT_FILE", "/data/metrics.jsonl"),
		ServerURL:       os.Getenv("SERVER_URL"),
		APIKey:          os.Getenv("API_KEY"),
	}

	if cfg.CollectInterval < 1 {
		return nil, fmt.Errorf("COLLECT_INTERVAL must be >= 1")
	}

	httpMode := cfg.ServerURL != "" || cfg.APIKey != ""
	if httpMode {
		if cfg.ServerURL == "" {
			return nil, fmt.Errorf("SERVER_URL is required when API_KEY is set")
		}
		if cfg.APIKey == "" {
			return nil, fmt.Errorf("API_KEY is required when SERVER_URL is set")
		}
	}

	return cfg, nil
}

func getEnv(key, fallback string) string {
	if v := os.Getenv(key); v != "" {
		return v
	}
	return fallback
}

func getEnvInt(key string, fallback int) int {
	if v := os.Getenv(key); v != "" {
		if n, err := strconv.Atoi(v); err == nil {
			return n
		}
	}
	return fallback
}

func hostname() string {
	h, err := os.Hostname()
	if err != nil {
		return "unknown"
	}
	return h
}
