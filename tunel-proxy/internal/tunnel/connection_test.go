package tunnel

import (
	"context"
	"testing"

	proto "github.com/hanaboso/pipes/tunel-proxy/proto"
	"google.golang.org/grpc/metadata"
)

type mockStream struct {
	proto.TunnelService_OpenTunnelServer
	ctx    context.Context
	sent   []*proto.Frame
	sendCh chan *proto.Frame
}

func newMockStream(ctx context.Context) *mockStream {
	return &mockStream{
		ctx:    ctx,
		sendCh: make(chan *proto.Frame, 100),
	}
}

func (m *mockStream) Send(frame *proto.Frame) error {
	m.sent = append(m.sent, frame)
	m.sendCh <- frame
	return nil
}

func (m *mockStream) Recv() (*proto.Frame, error) {
	return nil, nil
}

func (m *mockStream) Context() context.Context {
	return m.ctx
}

func (m *mockStream) SetHeader(metadata.MD) error  { return nil }
func (m *mockStream) SendHeader(metadata.MD) error { return nil }
func (m *mockStream) SetTrailer(metadata.MD)       {}
func (m *mockStream) SendMsg(interface{}) error    { return nil }
func (m *mockStream) RecvMsg(interface{}) error    { return nil }

func TestConnectionManager_RegisterAndGet(t *testing.T) {
	cm := NewConnectionManager()
	ctx, cancel := context.WithCancel(context.Background())
	defer cancel()

	stream := newMockStream(ctx)
	cm.Register("worker-1", stream, cancel)

	conn, ok := cm.Get("worker-1")
	if !ok {
		t.Fatal("expected to find worker-1")
	}
	if conn == nil {
		t.Fatal("expected non-nil connection")
	}
}

func TestConnectionManager_GetMissing(t *testing.T) {
	cm := NewConnectionManager()
	_, ok := cm.Get("nonexistent")
	if ok {
		t.Fatal("expected not to find nonexistent worker")
	}
}

func TestConnectionManager_RegisterReplacesOld(t *testing.T) {
	cm := NewConnectionManager()

	ctx1, cancel1 := context.WithCancel(context.Background())
	stream1 := newMockStream(ctx1)
	conn1 := cm.Register("worker-1", stream1, cancel1)

	ctx2, cancel2 := context.WithCancel(context.Background())
	defer cancel2()
	stream2 := newMockStream(ctx2)
	conn2 := cm.Register("worker-1", stream2, cancel2)

	if conn1 == conn2 {
		t.Fatal("expected different connection objects")
	}

	// Old context should be cancelled
	select {
	case <-ctx1.Done():
	default:
		t.Fatal("expected old context to be cancelled")
	}

	retrieved, ok := cm.Get("worker-1")
	if !ok {
		t.Fatal("expected to find worker-1")
	}
	if retrieved != conn2 {
		t.Fatal("expected the new connection")
	}
}

func TestConnectionManager_Unregister(t *testing.T) {
	cm := NewConnectionManager()
	ctx, cancel := context.WithCancel(context.Background())
	defer cancel()

	stream := newMockStream(ctx)
	conn := cm.Register("worker-1", stream, cancel)
	cm.Unregister("worker-1", conn)

	_, ok := cm.Get("worker-1")
	if ok {
		t.Fatal("expected worker-1 to be removed")
	}
}

func TestConnectionManager_UnregisterStaleDoesNotRemoveNew(t *testing.T) {
	cm := NewConnectionManager()

	ctx1, cancel1 := context.WithCancel(context.Background())
	defer cancel1()
	stream1 := newMockStream(ctx1)
	connOld := cm.Register("worker-1", stream1, cancel1)

	ctx2, cancel2 := context.WithCancel(context.Background())
	defer cancel2()
	stream2 := newMockStream(ctx2)
	cm.Register("worker-1", stream2, cancel2)

	// Unregistering the old conn should NOT remove the new one
	cm.Unregister("worker-1", connOld)

	_, ok := cm.Get("worker-1")
	if !ok {
		t.Fatal("new connection should still be registered")
	}
}

func TestConnectionManager_CloseAll(t *testing.T) {
	cm := NewConnectionManager()

	ctx1, cancel1 := context.WithCancel(context.Background())
	stream1 := newMockStream(ctx1)
	cm.Register("w1", stream1, cancel1)

	ctx2, cancel2 := context.WithCancel(context.Background())
	stream2 := newMockStream(ctx2)
	cm.Register("w2", stream2, cancel2)

	cm.CloseAll()

	if _, ok := cm.Get("w1"); ok {
		t.Fatal("w1 should be removed after CloseAll")
	}
	if _, ok := cm.Get("w2"); ok {
		t.Fatal("w2 should be removed after CloseAll")
	}

	select {
	case <-ctx1.Done():
	default:
		t.Fatal("ctx1 should be cancelled")
	}
	select {
	case <-ctx2.Done():
	default:
		t.Fatal("ctx2 should be cancelled")
	}
}

func TestWorkerConn_SendSerializes(t *testing.T) {
	cm := NewConnectionManager()
	ctx, cancel := context.WithCancel(context.Background())
	defer cancel()

	stream := newMockStream(ctx)
	conn := cm.Register("worker-1", stream, cancel)

	for i := 0; i < 10; i++ {
		err := conn.Send(&proto.Frame{RequestId: "test"})
		if err != nil {
			t.Fatalf("send failed: %v", err)
		}
	}

	if len(stream.sent) != 10 {
		t.Fatalf("expected 10 sent frames, got %d", len(stream.sent))
	}
}
