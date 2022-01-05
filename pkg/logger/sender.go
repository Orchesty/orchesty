package logger

import (
	"net"
)

type UdpSender struct {
	udp net.Conn
}

func (u UdpSender) Write(p []byte) (n int, err error) {
	return u.udp.Write(p)
}

func NewUdpSender() UdpSender {
	con, err := net.Dial("udp", "logstash:5120")
	if err != nil {
		panic(err)
	}

	return UdpSender{
		udp: con,
	}
}
