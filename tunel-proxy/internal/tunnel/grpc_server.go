package tunnel

import (
	"context"
	"io"
	"log/slog"

	proto "github.com/hanaboso/pipes/tunel-proxy/proto"
	"google.golang.org/grpc/codes"
	"google.golang.org/grpc/status"
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
		return status.Error(codes.InvalidArgument, "worker_id must not be empty")
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
		frame, err := stream.Recv()
		if err != nil {
			if err == io.EOF || ctx.Err() != nil {
				slog.Info("worker disconnected", "worker_id", workerID)
				return nil
			}
			slog.Error("recv error", "worker_id", workerID, "error", err)
			return err
		}

		if frame.RequestId == "" {
			slog.Warn("received frame without request_id, ignoring", "worker_id", workerID)
			continue
		}

		conn.pending.Complete(frame.RequestId, frame)
	}
}
