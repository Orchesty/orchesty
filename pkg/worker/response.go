package worker

import (
	"bytes"
	"context"
	"net/http"
	"time"

	"github.com/hanaboso/pipes/bridge/pkg/bridge/types"
	"github.com/hanaboso/pipes/bridge/pkg/enum"
	"github.com/hanaboso/pipes/bridge/pkg/model"
	"github.com/rs/zerolog/log"
)

// Response is a terminal worker that returns the current message payload to a
// caller-provided callback URL. The callback URL and request id are carried in
// the `reply-to` / `reply-id` headers, injected at the topology start (e.g. by
// Pulse) and propagated through the whole topology. When the headers are absent
// the node is a no-op so the same topology can run without a synchronous caller.
type Response struct {
	client http.Client
}

const responseCallbackTimeout = 30 * time.Second

func (r Response) BeforeProcess(_ types.Node, dto *model.ProcessMessage) model.ProcessResult {
	replyTo := dto.GetHeaderOrDefault(enum.Header_ReplyTo, "")
	replyId := dto.GetHeaderOrDefault(enum.Header_ReplyId, "")

	if replyTo == "" {
		// No synchronous caller is waiting for a response -> nothing to deliver.
		return dto.Ok()
	}

	req, err := http.NewRequest(http.MethodPost, replyTo, bytes.NewReader(dto.GetBody()))
	if err != nil {
		log.Error().EmbedObject(dto).Err(err).Msg("response node: failed to build callback request")
		return dto.Ok()
	}
	req.Header.Set("Content-Type", "application/json")
	if replyId != "" {
		req.Header.Set(enum.Header_ReplyId, replyId)
	}

	ctx, cancel := context.WithTimeout(context.Background(), responseCallbackTimeout)
	defer cancel()
	req = req.WithContext(ctx)

	resp, err := r.client.Do(req)
	if err != nil {
		// The caller will simply time out waiting; never fail the process for a
		// best-effort callback delivery.
		log.Error().EmbedObject(dto).Err(err).Msgf("response node: callback POST to %s failed", replyTo)
		return dto.Ok()
	}
	defer resp.Body.Close()

	log.Debug().EmbedObject(dto).
		Str(enum.LogHeader_LevelName, "debug").
		Msgf("response node: delivered payload to %s (status %d)", replyTo, resp.StatusCode)

	return dto.Ok()
}

// AfterProcess makes the response node terminal: it never forwards to followers.
func (Response) AfterProcess(_ types.Node, dto *model.ProcessMessage) (model.ProcessResult, int) {
	return dto.Stop(), 0
}
