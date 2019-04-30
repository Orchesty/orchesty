package tcp

import (
	"limiter/pkg/limiter"
	"limiter/pkg/logger"

	"bufio"
	"fmt"
	"net"
	"os"
	"os/signal"
	"strconv"
	"strings"
	"sync"
	"syscall"
	"time"
)

const (
	connHost = "localhost"
	connType = "tcp"

	healthCheckRequest       = "pf-health-check"
	healthCheckValidResponse = "ok"

	limitCheckRequest      = "pf-check"
	limitCheckResponseFree = "ok"
	limitCheckResponseBusy = "nok"
)

type request struct {
	name  string
	id    string
	key   string
	time  int
	value int
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

		timeParam, err := strconv.Atoi(data[3])
		if err != nil {
			return req, fmt.Errorf("invalid time param " + err.Error())
		}
		req.time = timeParam

		valueParam, err := strconv.Atoi(data[4])
		if err != nil {
			return req, fmt.Errorf("invalid value param " + err.Error())
		}
		req.value = valueParam

		return req, nil
	}

	return req, fmt.Errorf("unknown number of params")
}

// Server represents the HTTP server instance
type Server struct {
	lim      limiter.Limiter
	listener *net.TCPListener
	wg       *sync.WaitGroup
	logger   logger.Logger
}

// NewTCPServer creates new instance of TcpServer struct and returns pointer to it
func NewTCPServer(lim limiter.Limiter, logger logger.Logger) *Server {
	return &Server{
		lim:    lim,
		wg:     &sync.WaitGroup{},
		logger: logger,
	}
}

// Start starts the tcp server
func (srv *Server) Start(port int) {
	cmdAddr, _ := net.ResolveTCPAddr(connType, connHost+":"+strconv.Itoa(port))
	listener, err := net.ListenTCP(connType, cmdAddr)
	if err != nil {
		srv.logger.Fatal(fmt.Sprintf("TCP Server error listening: %s", err.Error()), logger.Context{"error": err})
	}

	srv.logger.Info(fmt.Sprintf("TCP server listening on port: %s", strconv.Itoa(port)), nil)

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
			// srv.logger.Error(fmt.Sprintf("Tcp Server error accepting: %s", err.Error()), nil)
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
func (srv *Server) Stop() {
	srv.logger.Info("Stopping TCP server", nil)
	srv.listener.Close()
	srv.wg.Wait()
}

// handleRequest handles incoming tcp request
func (srv *Server) handleRequest(conn net.Conn) {
	defer conn.Close()

	req, err := populateRequest(conn)
	if err != nil {
		srv.logger.Error(fmt.Sprintf("TCP server error reading request: %s", err), logger.Context{"error": err})
		return
	}
	srv.logger.Info(fmt.Sprintf("Tcp Server request received: %v", req), nil)
	result := ""

	switch req.name {
	case healthCheckRequest:
		result = srv.handleHealthCheckRequest(req)
		break
	case limitCheckRequest:
		result = srv.handleLimitCheckRequest(req)
		break
	}

	response := strings.Join([]string{req.name, req.id, result}, ";")
	conn.Write([]byte(response))

	srv.logger.Info(fmt.Sprintf("Tcp Server response sent: %s", response), nil)
}

// handleHealthCheckRequest just writes the given string to response which means that it is alive
func (*Server) handleHealthCheckRequest(req request) string {
	return healthCheckValidResponse
}

// handleLimitCheckRequest returns
func (srv *Server) handleLimitCheckRequest(req request) string {
	// send to elastic
	srv.logger.Metrics(req.key, "", nil)
	isFree, err := srv.lim.IsFreeLimit(req.key, req.time, req.value)
	if err != nil {
		return "Error evaluating limit: " + err.Error()
	}

	if isFree {
		return limitCheckResponseFree
	}

	return limitCheckResponseBusy
}
