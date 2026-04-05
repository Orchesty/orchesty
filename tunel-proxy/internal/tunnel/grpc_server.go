package tunnel

import (
	"context"
	"io"
	"log/slog"

	proto "github.com/hanaboso/pipes/tunel-proxy/proto"
)

type TunnelServer struct {
	proto.UnimplementedTunnelServiceServer
	cm *ConnectionManager
}

func NewTunnelServer(cm *ConnectionManager) *TunnelServer {
	return &TunnelServer{cm: cm}
}

func (s *TunnelServer) OpenTunnel(stream proto.TunnelService_OpenTunnelServer) error {
	first, err := stream.Recv()
	if err != nil {
		slog.Error("failed to receive identification frame", "error", err)
		return err
	}

	workerID := first.WorkerId
	if workerID == "" {
		slog.Error("worker sent empty worker_id in identification frame")
		return io.ErrUnexpectedEOF
	}

	ctx, cancel := context.WithCancel(stream.Context())
	conn := s.cm.Register(workerID, stream, cancel)

	defer func() {
		s.cm.Unregister(workerID, conn)
		conn.pending.CloseAll()
		cancel()
	}()

	slog.Info("starting recv loop", "worker_id", workerID)

	for {
		select {
		case <-ctx.Done():
			slog.Info("stream context cancelled", "worker_id", workerID)
			return nil
		default:
		}

		frame, err := stream.Recv()
		if err != nil {
			if err == io.EOF {
				slog.Info("worker disconnected (EOF)", "worker_id", workerID)
			} else {
				slog.Error("recv error", "worker_id", workerID, "error", err)
			}
			return err
		}

		if frame.RequestId == "" {
			slog.Warn("received frame without request_id, ignoring", "worker_id", workerID)
			continue
		}

		conn.pending.Complete(frame.RequestId, frame)
	}
}
