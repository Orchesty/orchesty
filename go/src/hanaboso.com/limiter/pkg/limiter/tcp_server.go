package limiter

import (
	"net"
	"fmt"
	"os"
	"bufio"
	"strings"
	"log"
	"os/signal"
	"syscall"
	"time"
	"sync"
)

const (
	connHost                 = "localhost"
	connPort                 = "3333"
	connType                 = "tcp"
	healthCheckRequest       = "pf-health-check"
	healthCheckValidResponse = "ok"
	limitCheckRequest        = "pf-check"
	limitCheckResponseOK     = "ok"
	limitCheckResponseNOK    = "nok"
)

type request struct {
	name  string
	id    string
	key   string
	time  string
	value string
}

func populateRequest(conn net.Conn) (request, error) {
	req := request{}

	msg, err := bufio.NewReader(conn).ReadString('\n')
	if err != nil {
		return req, err
	}

	msg = strings.TrimSpace(msg)
	data := strings.Split(msg, ";")

	// matches is ready request message
	if len(data) == 2 {
		req.name = data[0]
		req.id = data[1]

		return req, nil
	}

	// matches check limit request message
	if len(data) == 5 {
		req.name = data[0]
		req.id = data[1]
		req.key = data[2]
		req.time = data[3]
		req.value = data[4]

		return req, nil
	}

	return req, fmt.Errorf("unknown number of params")
}

type TcpServer struct {
	dec      Decider
	listener *net.TCPListener
	wg       *sync.WaitGroup
}

// NewTcpServer creates new instance of TcpServer struct and returns pointer to it
func NewTcpServer(dec Decider) *TcpServer {
	return &TcpServer{
		dec: dec,
		wg: &sync.WaitGroup{},
	}
}

// Start starts the tcp server
func (srv *TcpServer) Start() {
	cmdAddr, _ := net.ResolveTCPAddr(connType, connHost+":"+connPort)
	listener, err := net.ListenTCP(connType, cmdAddr)
	if err != nil {
		log.Fatalln("TCP Server error listening: ", err.Error())
	}

	log.Println("TCP server listening.")

	srv.listener = listener
	defer listener.Close()

	quitChan := make(chan os.Signal, 1)
	signal.Notify(quitChan, os.Interrupt, os.Kill, syscall.SIGTERM)

	for {
		listener.SetDeadline(time.Now().Add(1e9))
		conn, err := listener.AcceptTCP()
		if opErr, ok := err.(*net.OpError); ok && opErr.Timeout() {
			continue
		}
		if err != nil {
			log.Println("Tcp Server error accepting: ", err.Error())
			continue
		}
		srv.wg.Add(1)

		go func() {
			srv.wg.Done()
			srv.handleRequest(conn)
		}()
	}
}

// Stop stops the tcp server listener and waits for open goroutines to complete
func (srv *TcpServer) Stop() {
	srv.listener.Close()
	srv.wg.Wait()
}

// handleRequest handles incoming tcp request
func (srv *TcpServer) handleRequest(conn net.Conn) {
	defer conn.Close()

	req, err := populateRequest(conn)
	if err != nil {
		fmt.Println("TCP server error reading request:", err.Error())
		return
	}

	log.Println("Tcp Server request received.", req)

	switch req.name {
	case healthCheckRequest:
		srv.handleHealthCheckRequest(conn)
		return
	case limitCheckRequest:
		srv.handleLimitCheckRequest(conn, req)
		return
	}

	conn.Write([]byte("Invalid limiter request."))
}

// handleHealthCheckRequest just writes the given string to response which means that it is alive
func (*TcpServer) handleHealthCheckRequest(conn net.Conn) {
	conn.Write([]byte(healthCheckValidResponse))
}

// handleLimitCheckRequest returns
func (srv *TcpServer) handleLimitCheckRequest(conn net.Conn, req request) {
	isFree, err := srv.dec.Decide(req.key, req.time, req.value)
	if err != nil {
		fmt.Println("Error evaluating: ", err.Error())
		conn.Write([]byte(limitCheckResponseOK))
		return
	}

	if isFree {
		conn.Write([]byte(limitCheckResponseOK))
		return
	}

	conn.Write([]byte(limitCheckResponseNOK))
}
