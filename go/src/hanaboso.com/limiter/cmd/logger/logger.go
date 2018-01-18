package main

import "hanaboso.com/limiter/pkg/logger"

func main() {
	s := logger.NewUpdSender()
	s.Send()
}
