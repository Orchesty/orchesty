package app

import (
	"context"
	"limiter/pkg/mongo"
	"limiter/pkg/router"
	"net/http"
)

func StartServer(mongoSvc mongo.MongoSvc) (stopFunc func(ctx context.Context)) {
	server := &http.Server{Addr: ":8000", Handler: router.Router(router.Container{
		Mongo: mongoSvc,
	})}

	go server.ListenAndServe()

	return func(ctx context.Context) {
		_ = server.Shutdown(ctx)
	}
}
