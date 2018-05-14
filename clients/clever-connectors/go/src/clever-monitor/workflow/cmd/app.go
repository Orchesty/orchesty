package main

import (
	"strconv"
	"os"
	"clever-monitor/utils/env"
	"clever-monitor/workflow/pkg/handler"
	"clever-monitor/utils/logger"
	"clever-monitor/workflow/pkg/server"
	"clever-monitor/workflow/pkg/storage"
	"syscall"
	"os/signal"
	"clever-monitor/workflow/pkg/generator"
)

func main() {
	os.Setenv("APP_NAME", "workflow")

	prepareLogger()
	go runGrpcServer()

	gracefulShutdown()
}

// prepareLogger sets logger handlers
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

// runGrpcServer prepares and starts grpc server
func runGrpcServer() {
	addr := ":" + env.GetEnv("SERVER_PORT", "50051")

	db := storage.NewMongo(
		env.GetEnv("MONGO_HOST", "mongodb"),
		env.GetEnv("MONGO_DB", "workflow"),
		env.GetEnv("MONGO_EDITOR_COLLECTION", "wf_editor"),
		env.GetEnv("MONGO_WORKFLOW_COLLECTION", "wf_workflow"),
		logger.GetLogger(),
	)
	db.Connect()

	wfGenerator := generator.NewRecursiveGenerator()
	wfHandler := handler.NewWorkflowHandler(db, wfGenerator)

	grpcServer := server.NewServer(addr, wfHandler, logger.GetLogger())
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

