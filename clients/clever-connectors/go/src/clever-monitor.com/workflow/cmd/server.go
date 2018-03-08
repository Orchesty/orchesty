package main

import (
	"strconv"
	"os"
	"hanaboso/utils/env"
	"clever-monitor.com/workflow/pkg/handler"
	"clever-monitor.com/limiter/pkg/logger"
	"clever-monitor.com/workflow/pkg/server"
	"clever-monitor.com/workflow/pkg/storage"
)

func main() {
	os.Setenv("APP_NAME", "workflow")

	prepareLogger()
	runGrpcServer()
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

	wfHandler := handler.NewWorkflowHandler(db, logger.GetLogger())

	grpcServer := server.NewServer(addr, wfHandler, logger.GetLogger())
	grpcServer.Start()
}
