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

func NewUdpSender(url string) UdpSender {
	con, err := net.Dial("udp", url)
	if err != nil {
		panic(err)
	}

	return UdpSender{
		udp: con,
	}
}
