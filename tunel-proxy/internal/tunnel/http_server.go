package tunnel

import (
	"fmt"
	"io"
	"log/slog"
	"net/http"
	"time"

	"github.com/google/uuid"
	proto "github.com/hanaboso/pipes/tunel-proxy/proto"
)

type HTTPHandler struct {
	cm           *ConnectionManager
	timeout      int64
	maxBodyBytes int64
}

func NewHTTPHandler(cm *ConnectionManager, timeout int64, maxBodyBytes int64) *HTTPHandler {
	return &HTTPHandler{cm: cm, timeout: timeout, maxBodyBytes: maxBodyBytes}
}

func (h *HTTPHandler) RegisterRoutes(mux *http.ServeMux) {
	mux.HandleFunc("/call/{worker_id}/{path...}", h.handleCall)
	mux.HandleFunc("/health", h.handleHealth)
}

func (h *HTTPHandler) handleHealth(w http.ResponseWriter, _ *http.Request) {
	w.WriteHeader(http.StatusOK)
}

func (h *HTTPHandler) handleCall(w http.ResponseWriter, r *http.Request) {
	workerID := r.PathValue("worker_id")
	method := r.PathValue("path")

	conn, ok := h.cm.Get(workerID)
	if !ok {
		slog.Warn("worker not connected", "worker_id", workerID)
		http.Error(w, fmt.Sprintf("worker %q not connected", workerID), http.StatusNotFound)
		return
	}

	body, err := io.ReadAll(io.LimitReader(r.Body, h.maxBodyBytes))
	if err != nil {
		slog.Error("failed to read request body", "error", err)
		http.Error(w, "failed to read request body", http.StatusBadRequest)
		return
	}

	requestID := uuid.New().String()

	responseChan := conn.pending.Add(requestID)
	defer conn.pending.Remove(requestID)

	frame := &proto.Frame{
		WorkerId:   workerID,
		RequestId:  requestID,
		Method:     method,
		Payload:    body,
		HttpMethod: r.Method,
	}

	if err := conn.Send(frame); err != nil {
		slog.Error("failed to send frame to worker", "worker_id", workerID, "request_id", requestID, "error", err)
		http.Error(w, "failed to forward request to worker", http.StatusBadGateway)
		return
	}

	slog.Debug("frame sent, waiting for response", "worker_id", workerID, "request_id", requestID, "method", method)

	select {
	case resp, ok := <-responseChan:
		if !ok {
			slog.Warn("response channel closed (worker disconnected)", "worker_id", workerID, "request_id", requestID)
			http.Error(w, "worker disconnected while processing request", http.StatusBadGateway)
			return
		}
		statusCode := int(resp.StatusCode)
		if statusCode == 0 {
			statusCode = http.StatusOK
		}
		w.Header().Set("Content-Type", "application/json")
		w.WriteHeader(statusCode)
		if _, err := w.Write(resp.Payload); err != nil {
			slog.Debug("failed to write response", "worker_id", workerID, "request_id", requestID, "error", err)
		}

	case <-time.After(time.Duration(h.timeout) * time.Second):
		slog.Warn("request timed out", "worker_id", workerID, "request_id", requestID, "timeout", h.timeout)
		http.Error(w, "gateway timeout", http.StatusGatewayTimeout)

	case <-r.Context().Done():
		slog.Debug("client disconnected", "worker_id", workerID, "request_id", requestID)
	}
}
