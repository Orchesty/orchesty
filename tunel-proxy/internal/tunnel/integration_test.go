package tunnel_test

import (
	"bytes"
	"context"
	"fmt"
	"io"
	"net"
	"net/http"
	"testing"
	"time"

	"google.golang.org/grpc"
	"google.golang.org/grpc/credentials/insecure"
	"google.golang.org/grpc/keepalive"

	"github.com/hanaboso/pipes/tunel-proxy/internal/tunnel"
	proto "github.com/hanaboso/pipes/tunel-proxy/proto"
)

func startTestServers(t *testing.T) (httpAddr string, grpcAddr string, cleanup func()) {
	t.Helper()
	cm := tunnel.NewConnectionManager()

	grpcLis, err := net.Listen("tcp", "127.0.0.1:0")
	if err != nil {
		t.Fatalf("failed to listen for gRPC: %v", err)
	}
	grpcServer := grpc.NewServer(
		grpc.KeepaliveParams(keepalive.ServerParameters{
			Time:    30 * time.Second,
			Timeout: 10 * time.Second,
		}),
	)
	proto.RegisterTunnelServiceServer(grpcServer, tunnel.NewTunnelServer(cm))
	go grpcServer.Serve(grpcLis)

	mux := http.NewServeMux()
	httpHandler := tunnel.NewHTTPHandler(cm, 5*time.Second, 50*1024*1024)
	httpHandler.RegisterRoutes(mux)

	httpLis, err := net.Listen("tcp", "127.0.0.1:0")
	if err != nil {
		t.Fatalf("failed to listen for HTTP: %v", err)
	}
	httpServer := &http.Server{Handler: mux}
	go httpServer.Serve(httpLis)

	return httpLis.Addr().String(), grpcLis.Addr().String(), func() {
		grpcServer.GracefulStop()
		httpServer.Close()
		cm.CloseAll()
	}
}

func connectWorker(t *testing.T, grpcAddr, workerID string) (proto.TunnelService_OpenTunnelClient, func()) {
	t.Helper()
	conn, err := grpc.NewClient(
		grpcAddr,
		grpc.WithTransportCredentials(insecure.NewCredentials()),
	)
	if err != nil {
		t.Fatalf("failed to dial gRPC: %v", err)
	}

	client := proto.NewTunnelServiceClient(conn)
	stream, err := client.OpenTunnel(context.Background())
	if err != nil {
		conn.Close()
		t.Fatalf("failed to open tunnel: %v", err)
	}

	err = stream.Send(&proto.Frame{WorkerId: workerID})
	if err != nil {
		conn.Close()
		t.Fatalf("failed to send identification frame: %v", err)
	}

	return stream, func() {
		stream.CloseSend()
		conn.Close()
	}
}

func TestIntegration_FullFlow(t *testing.T) {
	httpAddr, grpcAddr, cleanup := startTestServers(t)
	defer cleanup()

	stream, closeWorker := connectWorker(t, grpcAddr, "test-worker")
	defer closeWorker()

	go func() {
		for {
			frame, err := stream.Recv()
			if err != nil {
				return
			}
			response := &proto.Frame{
				WorkerId:   frame.WorkerId,
				RequestId:  frame.RequestId,
				Method:     frame.Method,
				Payload:    []byte(fmt.Sprintf(`{"echo":"%s"}`, frame.Method)),
				StatusCode: 200,
			}
			stream.Send(response)
		}
	}()

	time.Sleep(100 * time.Millisecond)

	resp, err := http.Post(
		fmt.Sprintf("http://%s/call/test-worker/connector/hubspot/action", httpAddr),
		"application/json",
		bytes.NewBufferString(`{"key":"value"}`),
	)
	if err != nil {
		t.Fatalf("HTTP request failed: %v", err)
	}
	defer resp.Body.Close()

	if resp.StatusCode != 200 {
		t.Fatalf("expected 200, got %d", resp.StatusCode)
	}

	body, _ := io.ReadAll(resp.Body)
	expected := `{"echo":"connector/hubspot/action"}`
	if string(body) != expected {
		t.Fatalf("expected %q, got %q", expected, string(body))
	}
}

func TestIntegration_WorkerNotConnected(t *testing.T) {
	httpAddr, _, cleanup := startTestServers(t)
	defer cleanup()

	resp, err := http.Post(
		fmt.Sprintf("http://%s/call/missing-worker/process", httpAddr),
		"application/json",
		bytes.NewBufferString(`{}`),
	)
	if err != nil {
		t.Fatalf("HTTP request failed: %v", err)
	}
	defer resp.Body.Close()

	if resp.StatusCode != http.StatusNotFound {
		t.Fatalf("expected 404, got %d", resp.StatusCode)
	}
}

func TestIntegration_Timeout(t *testing.T) {
	httpAddr, grpcAddr, cleanup := startTestServers(t)
	defer cleanup()

	_, closeWorker := connectWorker(t, grpcAddr, "slow-worker")
	defer closeWorker()

	time.Sleep(100 * time.Millisecond)

	resp, err := http.Post(
		fmt.Sprintf("http://%s/call/slow-worker/process", httpAddr),
		"application/json",
		bytes.NewBufferString(`{}`),
	)
	if err != nil {
		t.Fatalf("HTTP request failed: %v", err)
	}
	defer resp.Body.Close()

	if resp.StatusCode != http.StatusGatewayTimeout {
		t.Fatalf("expected 504, got %d", resp.StatusCode)
	}
}

func TestIntegration_WorkerReconnect(t *testing.T) {
	httpAddr, grpcAddr, cleanup := startTestServers(t)
	defer cleanup()

	stream1, closeWorker1 := connectWorker(t, grpcAddr, "reconnect-worker")

	go func() {
		for {
			frame, err := stream1.Recv()
			if err != nil {
				return
			}
			stream1.Send(&proto.Frame{
				RequestId:  frame.RequestId,
				Payload:    []byte(`{"from":"stream1"}`),
				StatusCode: 200,
			})
		}
	}()
	time.Sleep(100 * time.Millisecond)

	closeWorker1()
	time.Sleep(100 * time.Millisecond)

	stream2, closeWorker2 := connectWorker(t, grpcAddr, "reconnect-worker")
	defer closeWorker2()

	go func() {
		for {
			frame, err := stream2.Recv()
			if err != nil {
				return
			}
			stream2.Send(&proto.Frame{
				RequestId:  frame.RequestId,
				Payload:    []byte(`{"from":"stream2"}`),
				StatusCode: 200,
			})
		}
	}()
	time.Sleep(100 * time.Millisecond)

	resp, err := http.Post(
		fmt.Sprintf("http://%s/call/reconnect-worker/process", httpAddr),
		"application/json",
		bytes.NewBufferString(`{}`),
	)
	if err != nil {
		t.Fatalf("HTTP request failed: %v", err)
	}
	defer resp.Body.Close()

	if resp.StatusCode != 200 {
		t.Fatalf("expected 200, got %d", resp.StatusCode)
	}

	body, _ := io.ReadAll(resp.Body)
	if string(body) != `{"from":"stream2"}` {
		t.Fatalf("expected response from stream2, got %q", string(body))
	}
}

func TestIntegration_MultipleWorkers(t *testing.T) {
	httpAddr, grpcAddr, cleanup := startTestServers(t)
	defer cleanup()

	for _, wid := range []string{"alpha", "beta"} {
		workerID := wid
		stream, closeW := connectWorker(t, grpcAddr, workerID)
		defer closeW()

		go func() {
			for {
				frame, err := stream.Recv()
				if err != nil {
					return
				}
				stream.Send(&proto.Frame{
					RequestId:  frame.RequestId,
					Payload:    []byte(fmt.Sprintf(`{"worker":"%s"}`, workerID)),
					StatusCode: 200,
				})
			}
		}()
	}

	time.Sleep(100 * time.Millisecond)

	for _, wid := range []string{"alpha", "beta"} {
		resp, err := http.Post(
			fmt.Sprintf("http://%s/call/%s/process", httpAddr, wid),
			"application/json",
			bytes.NewBufferString(`{}`),
		)
		if err != nil {
			t.Fatalf("HTTP request to %s failed: %v", wid, err)
		}

		body, _ := io.ReadAll(resp.Body)
		resp.Body.Close()

		expected := fmt.Sprintf(`{"worker":"%s"}`, wid)
		if string(body) != expected {
			t.Fatalf("worker %s: expected %q, got %q", wid, expected, string(body))
		}
	}
}
