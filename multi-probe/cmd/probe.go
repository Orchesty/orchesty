package main

import (
	"log"
	"net/http"
	"os"
	"os/signal"
	"syscall"
	"time"

	"multi-probe/pkg/probe"
)

// main runs the
func main() {
	storage := probe.GetStorage()

	var httpClient = http.Client{Timeout: time.Second * 10}
	var checker = probe.HttpCheck{Client: &httpClient}

	srv := probe.Server{Storage: storage, CheckerSvc: &checker}
	srv.Start(8007)

	gracefulShutdown(&srv)
}

// gracefulShutdown handles SIGINT and SIGTERM signal to stop the app gracefully
func gracefulShutdown(probe *probe.Server) {
	sigs := make(chan os.Signal, 1)
	done := make(chan bool, 1)

	signal.Notify(sigs, syscall.SIGINT, syscall.SIGTERM)

	go func() {
		sig := <-sigs
		log.Println()
		log.Println("Signal received: ", sig)

		probe.Stop()

		done <- true
	}()

	<-done
}
