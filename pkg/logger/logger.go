package logger

import (
	"fmt"
	"os"
	"strings"
	"sync"
)

// Level set severity level
type Level uint32

// severityLevel constants
const (
	FatalLevel Level = iota
	ErrorLevel
	WarnLevel
	InfoLevel
	DebugLevel
	TraceLevel
)

// Context is just map of strings
type Context map[string]interface{}

// Logger represents the application logger
type Logger interface {
	AddHandler(handler Handler)
	SetLevel(severity Level)
	Log(severityLevel Level, msg string, context Context)
	Debug(msg string, context Context)
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
	level    Level
}

func (l *logger) Log(severityLevel Level, msg string, context Context) {
	if severityLevel > l.level {
		return
	}

	if context == nil {
		context = Context{}
	}

	severity, err := severityLevel.MarshalText()
	if err != nil {
		fmt.Printf("marchal level failed => %s\n", err.Error())
		return
	}
	context["message"] = msg
	context["severity"] = string(severity)

	for _, h := range l.handlers {
		h.Handle(context)
	}
}

func (l *logger) Debug(msg string, context Context) {
	l.Log(DebugLevel, msg, context)
}

func (l *logger) Info(msg string, context Context) {
	l.Log(InfoLevel, msg, context)
}

func (l *logger) Error(msg string, context Context) {
	l.Log(ErrorLevel, msg, context)
}

func (l *logger) Warning(msg string, context Context) {
	l.Log(WarnLevel, msg, context)
}

func (l *logger) Fatal(msg string, context Context) {
	l.Log(FatalLevel, msg, context)
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

func (l *logger) SetLevel(severity Level) {
	l.level = severity
}

// NewLogger returns new Logger instance
func NewLogger() Logger {
	return &logger{
		level: InfoLevel,
	}
}

// MarshalText transform severity level to text format
func (level Level) MarshalText() ([]byte, error) {
	switch level {
	case TraceLevel:
		return []byte("trace"), nil
	case DebugLevel:
		return []byte("debug"), nil
	case InfoLevel:
		return []byte("info"), nil
	case WarnLevel:
		return []byte("warning"), nil
	case ErrorLevel:
		return []byte("error"), nil
	case FatalLevel:
		return []byte("fatal"), nil
	}

	return nil, fmt.Errorf("not a valid logrus level %d", level)
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

// ParseLevel get severity level representation from string
func ParseLevel(lvl string) (Level, error) {
	switch strings.ToLower(lvl) {
	case "fatal", "panic":
		return FatalLevel, nil
	case "error":
		return ErrorLevel, nil
	case "warn", "warning":
		return WarnLevel, nil
	case "info":
		return InfoLevel, nil
	case "debug":
		return DebugLevel, nil
	case "trace":
		return TraceLevel, nil
	}

	var l Level
	return l, fmt.Errorf("not a valid logrus Level: %q", lvl)
}
