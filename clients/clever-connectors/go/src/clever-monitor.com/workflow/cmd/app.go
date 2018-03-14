package main

import (
	"strconv"
	"os"
	"clever-monitor.com/utils/env"
	"clever-monitor.com/workflow/pkg/handler"
	"clever-monitor.com/limiter/pkg/logger"
	"clever-monitor.com/workflow/pkg/server"
	"clever-monitor.com/workflow/pkg/storage"
	"syscall"
	"os/signal"
)

func main() {
	os.Setenv("APP_NAME", "workflow")

	prepareLogger()
	go runGrpcServer()

	gracefulShutdown()
}

func prepareLogger() {
	logger.GetLogger().AddHandler(logger.NewLogStashHandler(logger.NewStdOutSender()))

	if enabled, _ := strconv.ParseBool(env.GetEnv("LOGSTASH_ENABLED", "false")); enabled {
		logger.GetLogger().AddHandler(
			logger.NewLogStashHandler(
				logger.NewUpdSender(
					env.GetEnv("LOGSTASH_HOST", "logstash"),
					env.GetEnv("LOGSTASH_PORT", "5120"),
				),
			),
		)
	}
}

func runGrpcServer() {
	addr := ":" + env.GetEnv("SERVER_PORT", "50051")

	db := storage.NewMongo(
		env.GetEnv("MONGO_HOST", "mongodb"),
		env.GetEnv("MONGO_DB", "workflow"),
		env.GetEnv("MONGO_COLLECTION", "workflow"),
		logger.GetLogger(),
	)
	db.Connect()

	wfHandler := handler.NewWorkflowHandler(db)
	cHandler := handler.NewConfigHandler(db)

	grpcServer := server.NewServer(addr, wfHandler, cHandler, logger.GetLogger())
	go grpcServer.Start()
}

// gracefulShutdown handles SIGINT and SIGTERM signal to stop the app gracefully
func gracefulShutdown() {
	sigs := make(chan os.Signal, 1)
	quit := make(chan bool, 1)

	signal.Notify(sigs, syscall.SIGINT, syscall.SIGTERM)

	go func() {
		sig := <-sigs
		logger.GetLogger().Info("Signal received: " + sig.String(), nil)

		quit <- true
	}()

	<-quit
}

