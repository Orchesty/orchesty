package main

import (
	"hanaboso.com/limiter/pkg/server"
	"os"
	"os/signal"
	"syscall"
	"log"
)

// main runs the limiter program
func main() {
	tcpServer := server.TcpServer{}
	go tcpServer.Start()

	gracefulShutdown(&tcpServer)
}

// gracefulShutdown handles SIGINT and SIGTERM signal to stop the app gracefully
func gracefulShutdown(tcpServer *server.TcpServer) {
	sigs := make(chan os.Signal, 1)
	done := make(chan bool, 1)

	signal.Notify(sigs, syscall.SIGINT, syscall.SIGTERM)

	go func() {
		sig := <-sigs
		log.Println()
		log.Println("Signal received: ", sig)

		// tcpServer.Stop()

		done <- true
	}()

	<-done
}


