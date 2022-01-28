package logger

import (
	"fmt"
	"log"
	"net"
	"time"
)

type updSender struct {
	url           string
	refreshTime   int
	addr          *net.UDPAddr
	conn          *net.UDPConn
	resolveTicker *time.Ticker
}

func (u *updSender) findHost() error {
	var err error
	u.addr, err = net.ResolveUDPAddr("udp", u.url)

	if err != nil {
		log.Println(fmt.Sprintf("Resolve ip addr for host %s error: %s", u.url, err))
		return err
	}

	return nil
}

func (u *updSender) resolveHost() {
	if u.resolveTicker == nil {
		u.resolveTicker = time.NewTicker(time.Second * time.Duration(u.refreshTime))
		go func() {
			for t := range u.resolveTicker.C {
				u.findHost()
				log.Println(fmt.Sprintf("Resolving host in %s", t))
			}
		}()
	}
}

func (u *updSender) Send(data []byte) {

	u.resolveHost()

	go func() {

		if u.addr == nil {
			resErr := u.findHost()

			if resErr != nil {
				log.Println(fmt.Sprintf("Udp resolve host error: %s", resErr))
				return
			}
		}

		if u.conn == nil {
			var err error
			u.conn, err = net.DialUDP("udp", nil, u.addr)

			if err != nil {
				log.Println(fmt.Sprintf("Udp sender coonection error: %s", err))
				return
			}
		}

		_, err := u.conn.Write(data)

		if err != nil {
			log.Println(fmt.Sprintf("Udp sender write error: %s", err))
		}

		return
	}()

}

// NewUpdSender creates new logger sender that send everything over UDP
func NewUpdSender(url string) Sender {
	return &updSender{url: url, refreshTime: 30}
}
