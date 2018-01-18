package logger

import (
	"net"
	"log"
	"fmt"
	"time"
)

type Sender interface {
	Send()
}

type updSender struct {
	host   string
	port   string
	ipAddr string
}

func (u *updSender) resolveHost() {
	ticker := time.NewTicker(time.Minute * 1)

	for t := range ticker.C {

		ip, err := net.LookupIP(u.host + u.port)

		log.Println(ip)

		if err != nil {
			log.Println(fmt.Sprintf("Upd sender error: %s", err))
		}

		log.Println(fmt.Sprintf("Resolve host for upd sender in %s", t))
	}
}

func (u *updSender) Send() {
	// @todo add start resolver
	log.Println(u.ipAddr+u.port)
	conn, err := net.Dial("udp", u.host+ ":" +u.port)

	if err != nil {
		log.Println(fmt.Sprintf("conn error: %s", err))
		return
	}

	_, err2 := conn.Write([]byte("test"))

	if err2 != nil {
		log.Println(fmt.Sprintf("write error: %s", err))
	}

	time.Sleep(time.Second * 3)
}

func NewUpdSender() Sender {
	return &updSender{host: "google.com", port: "80"}
}
