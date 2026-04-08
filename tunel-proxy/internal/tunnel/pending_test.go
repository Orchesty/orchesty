package tunnel

import (
	"sync"
	"testing"
	"time"

	proto "github.com/hanaboso/pipes/tunel-proxy/proto"
)

func TestPendingRequests_AddAndComplete(t *testing.T) {
	p := NewPendingRequests()
	ch := p.Add("req-1")

	go func() {
		p.Complete("req-1", &proto.Frame{
			RequestId:  "req-1",
			Payload:    []byte("hello"),
			StatusCode: 200,
		})
	}()

	select {
	case frame := <-ch:
		if frame.RequestId != "req-1" {
			t.Errorf("expected request_id req-1, got %s", frame.RequestId)
		}
		if string(frame.Payload) != "hello" {
			t.Errorf("expected payload 'hello', got %q", frame.Payload)
		}
		if frame.StatusCode != 200 {
			t.Errorf("expected status 200, got %d", frame.StatusCode)
		}
	case <-time.After(time.Second):
		t.Fatal("timed out waiting for response")
	}
}

func TestPendingRequests_CompleteUnknownID(t *testing.T) {
	p := NewPendingRequests()
	p.Complete("unknown", &proto.Frame{RequestId: "unknown"})
}

func TestPendingRequests_Remove(t *testing.T) {
	p := NewPendingRequests()
	ch := p.Add("req-2")
	p.Remove("req-2")

	select {
	case _, ok := <-ch:
		if ok {
			t.Fatal("expected channel to be closed")
		}
	case <-time.After(time.Second):
		t.Fatal("timed out waiting for channel close")
	}
}

func TestPendingRequests_RemoveUnknownID(t *testing.T) {
	p := NewPendingRequests()
	p.Remove("nonexistent")
}

func TestPendingRequests_CloseAll(t *testing.T) {
	p := NewPendingRequests()
	ch1 := p.Add("a")
	ch2 := p.Add("b")
	ch3 := p.Add("c")

	p.CloseAll()

	for i, ch := range []chan *proto.Frame{ch1, ch2, ch3} {
		select {
		case _, ok := <-ch:
			if ok {
				t.Errorf("channel %d: expected closed", i)
			}
		case <-time.After(time.Second):
			t.Fatalf("channel %d: timed out waiting for close", i)
		}
	}
}

func TestPendingRequests_ConcurrentAccess(t *testing.T) {
	p := NewPendingRequests()
	var wg sync.WaitGroup

	for i := 0; i < 100; i++ {
		wg.Add(1)
		go func(id string) {
			defer wg.Done()
			ch := p.Add(id)
			p.Complete(id, &proto.Frame{RequestId: id})
			<-ch
		}(time.Now().String() + string(rune(i)))
	}

	wg.Wait()
}
