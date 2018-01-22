package logger

import "sync"

// Context
type Context map[string]interface{}

// Logger
type Logger interface {
	AddHandler(handler Handler)
	Log(severity string, msg string, context Context)
}

// Formatter
type Formatter interface {
	Format(data map[string]interface{}) ([]byte, error)
}

// Sender
type Sender interface {
	Send(data []byte) error
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

	context["message"] = msg
	context["severity"] = severity

	for _, h := range l.handlers {
		h.Handle(context)
	}
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
