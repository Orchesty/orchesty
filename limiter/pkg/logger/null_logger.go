package logger

import (
	"os"
	"sync"
)

type nullLogger struct {
}

func (l *nullLogger) Log(severity string, msg string, context Context) {
	// void
}

func (l *nullLogger) Info(msg string, context Context) {
	// void
}

func (l *nullLogger) Error(msg string, context Context) {
	// void
}

func (l *nullLogger) Warning(msg string, context Context) {
	// void
}

func (l *nullLogger) Fatal(msg string, context Context) {
	os.Exit(1)
}

func (l *nullLogger) Metrics(key string, msg string, context Context) {
	//void
}

func (l *nullLogger) AddHandler(handler Handler) {
	// void
}

// Single instance for app
var (
	nl    Logger
	nOnce sync.Once
)

// GetNullLogger returns the null logger in singleton way
func GetNullLogger() Logger {
	nOnce.Do(func() {
		nl = &nullLogger{}
	})

	return nl
}
