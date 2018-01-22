package logger

import (
	"sync"
	"github.com/streadway/amqp"
	"os"
)

// Context
type Context map[string]interface{}

func CtxFromDelivery(m amqp.Delivery) Context {
	return Context{"headers": m.Headers, "body": string(m.Body)}
}

func CtxFromPublishing(m amqp.Publishing) Context {
	return Context{"headers": m.Headers, "body": string(m.Body)}
}

// Logger
type Logger interface {
	AddHandler(handler Handler)
	Log(severity string, msg string, context Context)
	Info(msg string, context Context)
	Error(msg string, context Context)
	Warning(msg string, context Context)
	Fatal(msg string, context Context)
}

// Formatter
type Formatter interface {
	Format(data map[string]interface{}) ([]byte, error)
}

// Sender
type Sender interface {
	Send(data []byte)
}

// Handler
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

func (l *logger) AddHandler(handler Handler) {
	l.handlers = append(l.handlers, handler)
}

func NewLogger() Logger {
	return &logger{}
}

// Single instance for app
var (
	l    Logger
	once sync.Once
)

func GetLogger() Logger {
	once.Do(func() {
		l = NewLogger()
	})

	return l
}
