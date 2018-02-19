package logger

// NewLogStashHandler creates default handler with logstash formatter
func NewLogStashHandler(s Sender) *DefaultHandler {
	return &DefaultHandler{s, &logStashFormatter{}}
}
