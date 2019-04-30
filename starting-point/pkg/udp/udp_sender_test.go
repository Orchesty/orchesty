package udp

import "testing"

func TestUDP(t *testing.T) {
	ConnectToUDP()

	UDP.Send([]byte{})
	UDP.DisconnectUDP()
}
