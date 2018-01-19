package limiter

import (
	"strconv"
	"net"
	"fmt"
	"bufio"
)

func CreateTcpHealtCheckRequestContent(reqID string) string {
	return "pf-health-check;"+reqID+"\n"
}

func CreateTcpCheckRequestContent(reqID string, key string, time int, val int) string {
	return "pf-check;"+reqID+";"+key+";"+strconv.Itoa(time)+";"+strconv.Itoa(val)+"\n"
}

func SendTcpPacket(addr string, content string) (string, error) {
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
