package udp

import (
	"fmt"
	"net"
	"starting-point/pkg/config"
	"time"

	log "github.com/sirupsen/logrus"
)

// Sender interface
type Sender interface {
	Send(data []byte)
	DisconnectUDP()
}

type udpSender struct {
	url           string
	refreshTime   int
	addr          *net.UDPAddr
	conn          *net.UDPConn
	resolveTicker *time.Ticker
}

// UDPSender describe
var UDPSender Sender

// ConnectToUDP init
func ConnectToUDP() {
	UDPSender = NewUDPSender()
}

func (u *udpSender) Send(data []byte) {
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

func (u *udpSender) DisconnectUDP() {
	err := u.conn.Close()

	if err != nil {
		log.Println(fmt.Sprintf("Resolving host error: %s", err))
	}
}

func (u *udpSender) findHost() error {
	var err error
	u.addr, err = net.ResolveUDPAddr("udp", u.url)

	if err != nil {
		log.Println(fmt.Sprintf("Resolve ip addr for host %s error: %s", u.url, err))
		return err
	}

	return nil
}

func (u *udpSender) resolveHost() {
	if u.resolveTicker == nil {
		u.resolveTicker = time.NewTicker(time.Second * time.Duration(u.refreshTime))
		go func() {
			for t := range u.resolveTicker.C {
				err := u.findHost()
				if err != nil {
					log.Println(fmt.Sprintf("Resolving host error: %s", err))
				}

				log.Println(fmt.Sprintf("Resolving host in %s", t))
			}
		}()
	}
}

// NewUDPSender construct
func NewUDPSender() Sender {
	return &udpSender{url: fmt.Sprintf("%s:%s", config.Config.InfluxDB.Hostname, config.Config.InfluxDB.Port), refreshTime: int(config.Config.InfluxDB.RefreshTime)}
}
