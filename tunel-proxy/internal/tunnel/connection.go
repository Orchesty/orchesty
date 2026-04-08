package tunnel

import (
	"context"
	"log/slog"
	"sync"

	proto "github.com/hanaboso/pipes/tunel-proxy/proto"
)

type workerConn struct {
	stream  proto.TunnelService_OpenTunnelServer
	pending *PendingRequests
	cancel  context.CancelFunc
	mu      sync.Mutex
}

func (w *workerConn) Send(frame *proto.Frame) error {
	w.mu.Lock()
	defer w.mu.Unlock()
	return w.stream.Send(frame)
}

type ConnectionManager struct {
	mu      sync.RWMutex
	workers map[string]*workerConn
}

func NewConnectionManager() *ConnectionManager {
	return &ConnectionManager{
		workers: make(map[string]*workerConn),
	}
}

func (cm *ConnectionManager) Register(workerID string, stream proto.TunnelService_OpenTunnelServer, cancel context.CancelFunc) *workerConn {
	conn := &workerConn{
		stream:  stream,
		pending: NewPendingRequests(),
		cancel:  cancel,
	}

	cm.mu.Lock()
	old, exists := cm.workers[workerID]
	cm.workers[workerID] = conn
	cm.mu.Unlock()

	if exists {
		slog.Warn("replacing existing worker connection", "worker_id", workerID)
		old.cancel()
		old.pending.CloseAll()
	}

	slog.Info("worker registered", "worker_id", workerID)
	return conn
}

func (cm *ConnectionManager) Unregister(workerID string, conn *workerConn) {
	cm.mu.Lock()
	current, ok := cm.workers[workerID]
	if ok && current == conn {
		delete(cm.workers, workerID)
	}
	cm.mu.Unlock()

	if ok && current == conn {
		slog.Info("worker unregistered", "worker_id", workerID)
	}
}

func (cm *ConnectionManager) Get(workerID string) (*workerConn, bool) {
	cm.mu.RLock()
	conn, ok := cm.workers[workerID]
	cm.mu.RUnlock()
	return conn, ok
}

func (cm *ConnectionManager) CloseAll() {
	cm.mu.Lock()
	for id, conn := range cm.workers {
		conn.cancel()
		conn.pending.CloseAll()
		delete(cm.workers, id)
	}
	cm.mu.Unlock()
}
