package tunnel

import (
	"sync"

	proto "github.com/hanaboso/pipes/tunel-proxy/proto"
)

type PendingRequests struct {
	mu       sync.Mutex
	requests map[string]chan *proto.Frame
}

func NewPendingRequests() *PendingRequests {
	return &PendingRequests{
		requests: make(map[string]chan *proto.Frame),
	}
}

func (p *PendingRequests) Add(requestID string) chan *proto.Frame {
	ch := make(chan *proto.Frame, 1)
	p.mu.Lock()
	p.requests[requestID] = ch
	p.mu.Unlock()
	return ch
}

func (p *PendingRequests) Complete(requestID string, frame *proto.Frame) {
	p.mu.Lock()
	ch, ok := p.requests[requestID]
	if ok {
		delete(p.requests, requestID)
	}
	p.mu.Unlock()

	if ok {
		ch <- frame
	}
}

func (p *PendingRequests) Remove(requestID string) {
	p.mu.Lock()
	ch, ok := p.requests[requestID]
	if ok {
		delete(p.requests, requestID)
		close(ch)
	}
	p.mu.Unlock()
}

func (p *PendingRequests) CloseAll() {
	p.mu.Lock()
	for id, ch := range p.requests {
		close(ch)
		delete(p.requests, id)
	}
	p.mu.Unlock()
}
