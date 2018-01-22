package logger

type Context map[string]interface{}

type Logger interface {
	AddHandler(handler Handler)
	Log(severity string, msg string, context Context)
}

type Formatter interface {
	Format(data map[string]interface{}) ([]byte, error)
}

type Sender interface {
	Send(data []byte) error
}

type Handler interface {
	Handle(data map[string]interface{})
}

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
