package limiter

import (
	"net"
	"fmt"
	"os"
	"bufio"
	"strings"
)

const (
	connHost = "localhost"
	connPort = "3333"
	connType = "tcp"
	healthCheckRequest = "pf-health-check"
	healthCheckValidResponse = "ok"
	limitCheckRequest = "pf-check"
	limitCheckResponseOK = "ok"
	limitCheckResponseNOK = "limit reached"
)

type TcpServer struct {

}

// Start starts the tcp server
func (tcp *TcpServer) Start() {
	// Listen for incoming connections.
	l, err := net.Listen(connType, connHost+":"+connPort)
	if err != nil {
		fmt.Println("Error listening:", err.Error())
		os.Exit(1)
	}
	// Close the listener when the application closes.
	defer l.Close()
	fmt.Println("Listening on " + connHost + ":" + connPort)
	for {
		// Listen for an incoming connection.
		conn, err := l.Accept()
		if err != nil {
			fmt.Println("Error accepting: ", err.Error())
			os.Exit(1)
		}
		// Handle connections in a new goroutine.
		go tcp.handleRequest(conn)
	}
}

// handleRequest handles incoming tcp request
func (tcp *TcpServer) handleRequest(conn net.Conn) {
	defer conn.Close()

	msg, err := bufio.NewReader(conn).ReadString('\n')
	if err != nil {
		fmt.Println("Error reading:", err.Error())
	}

	msg = strings.TrimSpace(msg)
	data := strings.Split(msg, ";")
	reqType := data[0]

	switch reqType {
	case healthCheckRequest:
		tcp.handleHealthCheckRequest(conn)
		return
	case limitCheckRequest:
		tcp.handleLimitCheckRequest(conn, data)
		return
	}

	conn.Write([]byte("Invalid limiter request."))
}

// handleHealthCheckRequest just writes the given string to response which means that it is alive
func (tcp *TcpServer) handleHealthCheckRequest(conn net.Conn) {
	conn.Write([]byte(healthCheckValidResponse))
}

// handleLimitCheckRequest returns
func (tcp *TcpServer) handleLimitCheckRequest(conn net.Conn, data []string) {
	isFree, err := Evaluate(data[1], data[2], data[3])
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
