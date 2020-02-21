package udp

import (
	"fmt"
	"net"
	"time"

	"starting-point/pkg/config"

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

// UDP describe
var UDP Sender

// ConnectToUDP init
func ConnectToUDP() {
	UDP = NewUDPSender()
}

func (u *udpSender) Send(data []byte) {
	u.resolveHost()

	go func() {
		if u.addr == nil {
			resErr := u.findHost()

			if resErr != nil {
				log.Error(fmt.Sprintf("Udp resolve host error: %+v", resErr))
				return
			}
		}

		if u.conn == nil {
			var err error
			u.conn, err = net.DialUDP("udp", nil, u.addr)

			if err != nil {
				log.Error(fmt.Sprintf("Udp sender connection error: %+v", err))
				return
			}
		}

		_, err := u.conn.Write(data)

		if err != nil {
			log.Error(fmt.Sprintf("Udp sender write error: %+v", err))
		}

		return
	}()
}

func (u *udpSender) DisconnectUDP() {
	if u.conn != nil {
		err := u.conn.Close()

		if err != nil {
			log.Error(fmt.Sprintf("Resolving host error: %+v", err))
		}
	}
}

func (u *udpSender) findHost() error {
	var err error
	u.addr, err = net.ResolveUDPAddr("udp", u.url)

	if err != nil {
		log.Error(fmt.Sprintf("Resolve ip addr for host %s error: %+v", u.url, err))
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
					log.Error(fmt.Sprintf("Resolving host error: %+v", err))
				}

				log.Info(fmt.Sprintf("Resolving host in %s", t))
			}
		}()
	}
}

// NewUDPSender construct
func NewUDPSender() Sender {
	return &udpSender{url: fmt.Sprintf("%s:%s", config.Config.InfluxDB.Hostname, config.Config.InfluxDB.Port), refreshTime: int(config.Config.InfluxDB.RefreshTime)}
}
