package logger

import "sync"

type nullLogger struct {
}

func (l *nullLogger) Log(severity string, msg string, context Context) {
	// void
}

func (l *nullLogger) Info(msg string, context Context) {
	l.Log("info", msg, context)
}

func (l *nullLogger) Error(msg string, context Context) {
	l.Log("error", msg, context)
}

func (l *nullLogger) Warning(msg string, context Context) {
	l.Log("warning", msg, context)
}

func (l *nullLogger) AddHandler(handler Handler) {
	// void
}

// Single instance for app
var (
	nl    Logger
	nOnce sync.Once
)

func GetNullLogger() Logger {
	nOnce.Do(func() {
		nl = &nullLogger{}
	})

	return nl
}
