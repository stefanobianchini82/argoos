package main

import (
	"log"
	"time"

	"github.com/argoos/agent/internal/collector"
	"github.com/argoos/agent/internal/config"
	"github.com/argoos/agent/internal/sender"
)

func main() {
	cfg, err := config.Load()
	if err != nil {
		log.Fatalf("config: %v", err)
	}

	log.Printf("starting argoos-agent — host=%s interval=%ds output=%s",
		cfg.HostLabel, cfg.CollectInterval, cfg.OutputFile)

	col := collector.New()
	col.Prime()
	log.Println("collector primed")

	// Phase 1: write metrics to a local file.
	// Phase 2: replace with sender.NewHTTPSender(cfg.ServerURL, cfg.APIKey, cfg.RetryAttempts)
	snd := sender.NewFileSender(cfg.OutputFile)

	ticker := time.NewTicker(time.Duration(cfg.CollectInterval) * time.Second)
	defer ticker.Stop()

	for range ticker.C {
		m, err := col.Collect()
		if err != nil {
			log.Printf("collect error: %v", err)
			continue
		}

		if err := snd.Send(m); err != nil {
			log.Printf("send error: %v", err)
			continue
		}

		log.Printf("metric saved — cpu=%.1f%% ram=%dMB/%dMB",
			m.CPUUsage, m.RAMUsed/1024/1024, m.RAMTotal/1024/1024)
	}
}
