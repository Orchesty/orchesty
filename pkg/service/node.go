package service

import (
	"context"
	"github.com/streadway/amqp"
	"go.uber.org/zap"
	"time"
)

type Node struct {
	Connection *amqp.Connection // TODO abstraction of rabbit and memory?
	Timeout    time.Duration
	Worker     interface{} // TODO review service.worker
}

func (n *Node) Start(ctx context.Context) {

	go zap.S().Info("TODO connect to consumer")

	// Wait for terminating signal
	<-ctx.Done()

	// Shutdown logic

	shutdownCtx, cancel := context.WithTimeout(context.Background(), n.Timeout)
	defer cancel() // Stop timer if shutdown finish in time

	n.shutdown(shutdownCtx)

	zap.S().Info("Bridge stopped")
}

func (n *Node) subscribe() {

}

func (n *Node) publish() {

}

func (n *Node) shutdown(ctx context.Context) {
	done := make(chan struct{})

	go func() {
		defer close(done)
		zap.S().Info("TODO Shutdown logic")
	}()

	select {
	case <-done:
	case <-ctx.Done():
		zap.S().Warnf("Node shutdown takes more than expected %v", n.Timeout)
	}
}
