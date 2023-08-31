package tcp

import (
	"bufio"
	"fmt"
	"net"
	"strconv"
)

// CreateTCPHealthCheckRequestContent returns the body of health check HTTP request
func CreateTCPHealthCheckRequestContent(reqID string) string {
	return "health-check;" + reqID + "\n"
}

// CreateTCPCheckRequestContent returns the body of tcp check HTTP request
func CreateTCPCheckRequestContent(reqID string, key string, time int, val int) string {
	return "check;" + reqID + ";" + key + ";" + strconv.Itoa(time) + ";" + strconv.Itoa(val) + "\n"
}

// SendTCPPacket send packet over network
func SendTCPPacket(addr string, content string) (string, error) {
	conn, err := net.Dial("tcp", addr)
	if err != nil {
		return "", fmt.Errorf("Could not create tcp connection. Err:" + err.Error())
	}
	for {
		fmt.Fprintf(conn, content)
		response, _ := bufio.NewReader(conn).ReadString('\n')
		return response, nil
	}
}
