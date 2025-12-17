package limiter

type Message struct {
	MessageId  string `json:"messageId"`
	LimiterKey string `json:"limiterKey"`
	Ok         bool   `json:"ok"`
}
