package logger

import (
	"fmt"
	"log"
	"net"
	"time"
)

type udpSender struct {
	url           string
	refreshTime   int
	addr          *net.UDPAddr
	conn          *net.UDPConn
	resolveTicker *time.Ticker
}

func (u *udpSender) findHost() error {
	var err error
	u.addr, err = net.ResolveUDPAddr("udp", u.url)

	if err != nil {
		log.Printf("Resolve IP addr for host %s error: %s", u.url, err)
		return err
	}

	return nil
}

func (u *udpSender) resolveHost() {
	if u.resolveTicker == nil {
		u.resolveTicker = time.NewTicker(time.Second * time.Duration(u.refreshTime))
		go func() {
			for t := range u.resolveTicker.C {
				u.findHost()
				log.Printf("Resolving host in %s", t)
			}
		}()
	}
}

func (u *udpSender) Send(data []byte) {

	u.resolveHost()

	go func() {

		if u.addr == nil {
			resErr := u.findHost()

			if resErr != nil {
				log.Printf("UDP resolve host error: %s", resErr)
				return
			}
		}

		if u.conn == nil {
			var err error
			u.conn, err = net.DialUDP("udp", nil, u.addr)

			if err != nil {
				log.Printf("UDP sender coonection error: %s", err)
				return
			}
		}

		_, err := u.conn.Write(data)

		if err != nil {
			log.Printf("UDP sender write error: %s", err)
		}

		return
	}()
}

// NewUDPSender creates Sender to target host and port
func NewUDPSender(host string, port string) Sender {
	return &udpSender{url: fmt.Sprintf("%s:%s", host, port), refreshTime: 30}
}
