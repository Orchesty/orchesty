package logger

import (
	"os"
	"sync"
)

type nullLogger struct {
}

func (l *nullLogger) SetLevel(_ Level) {
	// void
}

func (l *nullLogger) Debug(_ string, _ Context) {
	// void
}

func (l *nullLogger) Log(_ Level, _ string, _ Context) {
	// void
}

func (l *nullLogger) Info(_ string, _ Context) {
	// void
}

func (l *nullLogger) Error(_ string, _ Context) {
	// void
}

func (l *nullLogger) Warning(_ string, _ Context) {
	// void
}

func (l *nullLogger) Fatal(_ string, _ Context) {
	os.Exit(1)
}

func (l *nullLogger) Metrics(_ string, _ string, _ Context) {
	// void
}

func (l *nullLogger) AddHandler(_ Handler) {
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
