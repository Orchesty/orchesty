package main

import (
	"context"
	"fmt"
	"log/slog"
	"net"
	"net/http"
	"os"
	"os/signal"
	"syscall"
	"time"

	"google.golang.org/grpc"
	"google.golang.org/grpc/keepalive"

	"github.com/hanaboso/pipes/tunel-proxy/internal/config"
	"github.com/hanaboso/pipes/tunel-proxy/internal/tunnel"
	proto "github.com/hanaboso/pipes/tunel-proxy/proto"
)

func main() {
	slog.SetDefault(slog.New(slog.NewJSONHandler(os.Stdout, &slog.HandlerOptions{Level: slog.LevelDebug})))

	cfg := config.Load()
	cm := tunnel.NewConnectionManager()

	grpcServer := grpc.NewServer(
		grpc.KeepaliveParams(keepalive.ServerParameters{
			MaxConnectionIdle: 5 * time.Minute,
			Time:              30 * time.Second,
			Timeout:           10 * time.Second,
		}),
		grpc.KeepaliveEnforcementPolicy(keepalive.EnforcementPolicy{
			MinTime:             10 * time.Second,
			PermitWithoutStream: true,
		}),
	)
	proto.RegisterTunnelServiceServer(grpcServer, tunnel.NewTunnelServer(cm))

	mux := http.NewServeMux()
	httpHandler := tunnel.NewHTTPHandler(cm, cfg.RequestTimeout, cfg.MaxRequestBytes)
	httpHandler.RegisterRoutes(mux)

	httpServer := &http.Server{
		Addr:    ":8080",
		Handler: mux,
	}

	errCh := make(chan error, 2)

	go func() {
		lis, err := net.Listen("tcp", ":50051")
		if err != nil {
			errCh <- fmt.Errorf("gRPC listen on %s: %w", ":50051", err)
			return
		}
		slog.Info("gRPC server listening", "addr", ":50051")
		if err := grpcServer.Serve(lis); err != nil {
			errCh <- fmt.Errorf("gRPC serve: %w", err)
		}
	}()

	go func() {
		slog.Info("HTTP server listening", "addr", ":8080")
		if err := httpServer.ListenAndServe(); err != nil && err != http.ErrServerClosed {
			errCh <- fmt.Errorf("HTTP serve: %w", err)
		}
	}()

	sigChan := make(chan os.Signal, 1)
	signal.Notify(sigChan, syscall.SIGINT, syscall.SIGTERM)

	select {
	case sig := <-sigChan:
		slog.Info("received signal, shutting down", "signal", sig)
	case err := <-errCh:
		slog.Error("server failed, shutting down", "error", err)
	}

	grpcServer.GracefulStop()

	ctx, cancel := context.WithTimeout(context.Background(), 15*time.Second)
	defer cancel()
	if err := httpServer.Shutdown(ctx); err != nil {
		slog.Error("HTTP server shutdown error", "error", err)
	}

	cm.CloseAll()
	slog.Info("shutdown complete")
}
