package main

import (
	"hanaboso/utils/env"

	"clever-monitor.com/limiter/pkg/logger"
	"clever-monitor.com/workflow/pkg/server"
)

func main() {
	prepareLogger()
	prepareGrpcServer()
}

func prepareLogger() {
	logger.GetLogger().AddHandler(logger.NewLogStashHandler(logger.NewStdOutSender()))
	logger.GetLogger().AddHandler(
		logger.NewLogStashHandler(
			logger.NewUpdSender(
				env.GetEnv("LOGSTASH_HOST", "logstash"),
				env.GetEnv("LOGSTASH_PORT", "5120"),
			),
		),
	)
}

func prepareGrpcServer() {
	addr := ":" + env.GetEnv("SERVER_PORT", "50051")
	grpcServer := server.NewServer(addr, logger.GetLogger())
	grpcServer.Start()
}
