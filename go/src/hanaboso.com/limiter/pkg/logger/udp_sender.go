package logger

import (
	"net"
	"log"
	"fmt"
	"time"
)

type updSender struct {
	host        string
	port        string
	refreshTime int
	addr        *net.UDPAddr
	conn        *net.UDPConn
}

func (u *updSender) findHost(url string) error {
	var err error
	u.addr, err = net.ResolveUDPAddr("udp", url)

	if err != nil {
		log.Println(fmt.Sprintf("Resolve ip addr error: %s", err))
		return err
	}

	return nil
}

func (u *updSender) resolveHost(url string) error {

	err := u.findHost(url)

	if err != nil {
		return err
	}

	ticker := time.NewTicker(time.Second * time.Duration(u.refreshTime))
	go func() {
		for t := range ticker.C {
			u.findHost(url)
			log.Println(fmt.Sprintf("Resolving host in %s", t))
		}
	}()

	return nil
}

func (u *updSender) Send(data []byte) {

	go func() {
		resErr := u.resolveHost(fmt.Sprintf("%s:%s", u.host, u.port))

		if resErr != nil {
			log.Println(fmt.Sprintf("Udp resolve host error: %s", resErr))
			return
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

func NewUpdSender(host string, port string) Sender {
	return &updSender{host: host, port: port, refreshTime: 60}
}
