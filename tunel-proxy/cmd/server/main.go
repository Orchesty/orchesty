package main

import (
	"context"
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
	httpHandler := tunnel.NewHTTPHandler(cm, cfg.RequestTimeout)
	httpHandler.RegisterRoutes(mux)

	httpServer := &http.Server{
		Addr:    cfg.HTTPAddr,
		Handler: mux,
	}

	go func() {
		lis, err := net.Listen("tcp", cfg.GRPCAddr)
		if err != nil {
			slog.Error("failed to listen on gRPC address", "addr", cfg.GRPCAddr, "error", err)
			os.Exit(1)
		}
		slog.Info("gRPC server listening", "addr", cfg.GRPCAddr)
		if err := grpcServer.Serve(lis); err != nil {
			slog.Error("gRPC server error", "error", err)
		}
	}()

	go func() {
		slog.Info("HTTP server listening", "addr", cfg.HTTPAddr)
		if err := httpServer.ListenAndServe(); err != nil && err != http.ErrServerClosed {
			slog.Error("HTTP server error", "error", err)
			os.Exit(1)
		}
	}()

	sigChan := make(chan os.Signal, 1)
	signal.Notify(sigChan, syscall.SIGINT, syscall.SIGTERM)
	sig := <-sigChan
	slog.Info("received signal, shutting down", "signal", sig)

	grpcServer.GracefulStop()

	ctx, cancel := context.WithTimeout(context.Background(), 15*time.Second)
	defer cancel()
	if err := httpServer.Shutdown(ctx); err != nil {
		slog.Error("HTTP server shutdown error", "error", err)
	}

	cm.CloseAll()
	slog.Info("shutdown complete")
}
