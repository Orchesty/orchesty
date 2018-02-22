package logger

// NewLogStashHandler creates default handler with logstash formatter
func NewLogStashHandler(s Sender, app string) *DefaultHandler {
	return &DefaultHandler{s, &logStashFormatter{app}}
}
