package log

import (
	"fmt"
	"hanaboso/utils/env"
	"hanaboso/utils/logger"
)

const logApp = "topology-api"

var log logger.Logger

func init() {
	log = logger.GetLogger()
	log.AddHandler(&logger.DefaultHandler{
		Sender:    logger.NewStdOutSender(),
		Formatter: logger.NewLogStashFormatter(logApp),
	})
	log.AddHandler(
		logger.NewLogStashHandler(
			logger.NewUDPSender(
				env.GetEnv("LOGSTASH_HOST", "logstash"),
				env.GetEnv("LOGSTASH_PORT", "5120"),
			),
			logApp,
		),
	)
}

// Info log message
func Info(msg string) {
	log.Info(msg, nil)
}

// Infof formats log message
func Infof(format string, a ...interface{}) {
	log.Info(fmt.Sprintf(format, a...), nil)
}

// Fatal log message
func Fatal(e error) {
	log.Fatal(e.Error(), nil)
}

// Fatalf formats log message
func Fatalf(format string, a ...interface{}) {
	log.Fatal(fmt.Sprintf(format, a...), nil)
}
