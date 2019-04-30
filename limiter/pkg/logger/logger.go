package logger

import (
	"os"
	"strings"
	"sync"
)

// Context is just map of strings
type Context map[string]interface{}

// Logger represents the application logger
type Logger interface {
	AddHandler(handler Handler)
	Log(severity string, msg string, context Context)
	Info(msg string, context Context)
	Error(msg string, context Context)
	Warning(msg string, context Context)
	Fatal(msg string, context Context)
	Metrics(key string, msg string, context Context)
}

// Formatter formats data
type Formatter interface {
	Format(data map[string]interface{}) ([]byte, error)
}

// Sender send the data
type Sender interface {
	Send(data []byte)
}

// Handler handles the given data
type Handler interface {
	Handle(data map[string]interface{})
}

// Logger implementation
type logger struct {
	handlers []Handler
}

func (l *logger) Log(severity string, msg string, context Context) {

	if context == nil {
		context = Context{}
	}

	context["message"] = msg
	context["severity"] = severity

	for _, h := range l.handlers {
		h.Handle(context)
	}
}

func (l *logger) Info(msg string, context Context) {
	l.Log("info", msg, context)
}

func (l *logger) Error(msg string, context Context) {
	l.Log("error", msg, context)
}

func (l *logger) Warning(msg string, context Context) {
	l.Log("warning", msg, context)
}

func (l *logger) Fatal(msg string, context Context) {
	l.Log("warning", msg, context)
	os.Exit(1)
}

func (l *logger) Metrics(key string, msg string, context Context) {

	data := strings.Split(key, "|")

	if len(data) == 0 {
		return
	}

	if context == nil {
		context = Context{}
	}

	if len(data) >= 1 {
		context["system_key"] = data[0]
	}

	if len(data) >= 2 {
		context["guid"] = data[1]
	}

	l.Info(msg, context)
}

func (l *logger) AddHandler(handler Handler) {
	l.handlers = append(l.handlers, handler)
}

// NewLogger returns new Logger instance
func NewLogger() Logger {
	return &logger{}
}

// Single instance for app
var (
	l    Logger
	once sync.Once
)

// GetLogger returns a logger instance in a singleton way
func GetLogger() Logger {
	once.Do(func() {
		l = NewLogger()
	})

	return l
}
