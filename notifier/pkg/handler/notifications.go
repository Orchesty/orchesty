package handler

import (
	"encoding/json"
	"fmt"
	"net/http"

	"notifier/pkg/service"
)

func HandleNotificationStream(writer http.ResponseWriter, request *http.Request) {
	flusher, ok := writer.(http.Flusher)
	if !ok {
		http.Error(writer, "Streaming unsupported", http.StatusInternalServerError)
		return
	}

	writer.Header().Set("Content-Type", "text/event-stream")
	writer.Header().Set("Cache-Control", "no-cache")
	writer.Header().Set("Connection", "keep-alive")

	ch := service.Container.SSEBroadcaster.Subscribe()
	defer service.Container.SSEBroadcaster.Unsubscribe(ch)

	ctx := request.Context()

	for {
		select {
		case <-ctx.Done():
			return
		case n, ok := <-ch:
			if !ok {
				return
			}

			data, err := json.Marshal(n)
			if err != nil {
				logContext().Error(fmt.Errorf("failed to marshal notification for SSE: %v", err))
				continue
			}

			fmt.Fprintf(writer, "data: %s\n\n", data)
			flusher.Flush()
		}
	}
}
