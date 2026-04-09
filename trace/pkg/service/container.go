package service

import (
	"net/http"
	"time"

	"trace/pkg/config"
	"trace/pkg/sender"
)

var Container container

var shutdown func()

type container struct {
	StatusService   StatusService
	AuthService     AuthService
	ManifestService ManifestService
	AIService       AIService
	TraceService    TraceService
}

func Load() error {
	httpSender := sender.NewHttpSender(&http.Client{
		Timeout: time.Duration(config.Backend.Timeout) * time.Second,
	}, config.Logger)

	manifestService := NewManifestService(httpSender, config.Backend.URL, config.Logger)
	aiService := NewAIService(httpSender, config.Backend.URL, config.Logger)

	Container = container{
		StatusService:   NewStatusService(httpSender, config.Backend.URL),
		AuthService:     NewAuthService(httpSender, config.Backend.URL, config.Logger),
		ManifestService: manifestService,
		AIService:       aiService,
		TraceService:    NewTraceService(manifestService, aiService, config.Logger),
	}

	shutdown = func() {}

	return nil
}

func Shutdown() {
	if shutdown != nil {
		shutdown()
	}
}
