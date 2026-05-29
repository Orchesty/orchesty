package worker

import (
	"bytes"
	"context"
	"encoding/json"
	"fmt"
	"net/http"
	"time"

	"github.com/hanaboso/pipes/bridge/pkg/audit"
	"github.com/hanaboso/pipes/bridge/pkg/bridge/types"
	"github.com/hanaboso/pipes/bridge/pkg/config"
	"github.com/hanaboso/pipes/bridge/pkg/enum"
	"github.com/hanaboso/pipes/bridge/pkg/model"
	"github.com/rs/zerolog/log"
)

type httpBeforeProcess struct {
	client http.Client
}

type Http struct {
	httpBeforeProcess
	broadcastAfterProcess
}

func (h httpBeforeProcess) BeforeProcess(node types.Node, dto *model.ProcessMessage) model.ProcessResult {
	host := node.Settings().Url
	nodeId := node.Id()
	correlationId := dto.GetHeaderOrDefault(enum.Header_CorrelationId, "")

	if IsPoisoned(host, nodeId, correlationId) {
		return dto.Trash(fmt.Errorf("worker unavailable, correlationId poisoned"))
	}

	if !CanSend(host, nodeId) {
		time.Sleep(delaySec * time.Second)
		return dto.Error(fmt.Errorf("sdk was unreachable, delaying message"))
	}

	dto.KeepRepeatHeaders = true
	dto.ClearHeaders()

	messageBody := model.MessageDto{
		Body:    string(dto.GetBody()),
		Headers: dto.Headers,
	}
	if messageBody.Headers == nil {
		messageBody.Headers = make(map[string]interface{})
	}

	for key, value := range node.Settings().Headers {
		messageBody.Headers[key] = fmt.Sprint(value)
	}

	marshaled, _ := json.Marshal(&messageBody)
	body := bytes.NewBuffer(marshaled)
	req, err := http.NewRequest("POST", node.Settings().ActionUrl(), body)
	if err != nil {
		return dto.Error(err)
	}

	ctx, cancel := context.WithTimeout(context.Background(), 60*time.Second)
	defer cancel()
	req = req.WithContext(ctx)

	// Demoted to DEBUG: this log historically dumped the full request body and
	// every header (including Authorization, Cookie, ...) at INFO on every
	// worker call. The audit signal we actually want is now produced by
	// AuditCheckpointNode + the audit package below; verbose tracing remains
	// available via BRIDGE_LOG_LEVEL=debug.
	log.Debug().EmbedObject(dto).
		Str(enum.LogHeader_LevelName, "debug").
		Interface("reqHeaders", audit.SanitizeHeadersHttp(req.Header)).
		Interface("reqBody", messageBody).
		Msgf("Incoming request: Method[POST] Url[/%s]", node.Settings().ActionPath)

	startTime := time.Now()
	response, err := h.client.Do(req)
	if err != nil {
		RecordFailure(host, nodeId, correlationId)
		if IsPoisoned(host, nodeId, correlationId) {
			log.Warn().EmbedObject(dto).
				Bool(enum.LogHeader_IsForUi, true).
				Msgf("Worker %s unreachable, poisoning correlationId %s after %d failures", host, correlationId, config.App.WorkerMaxFailures)
			return dto.Trash(fmt.Errorf("worker unreachable, correlationId poisoned after %d failures", config.App.WorkerMaxFailures))
		}
		return dto.Error(err)
	}
	defer response.Body.Close()
	duration := time.Since(startTime)

	log.Debug().EmbedObject(dto).
		Str(enum.LogHeader_LevelName, "debug").
		Msgf("Total request duration: %dms for endpoint POST[/%s]", duration.Milliseconds(), node.Settings().ActionPath)

	dto.FromHttpResponse(response)

	// Business audit checkpoint emission.
	//
	// Any node may carry an audit checkpoint declaration:
	// - input/output Connector overriding `getAuditCheckpoint()` -> entry/exit
	//   audit captures the data the connector actually produced/delivered + the
	//   delivery outcome (`resultCode/resultStatus/resultMessage/httpStatus`).
	// - AuditCheckpointNode passthrough -> step markers / non-connector
	//   boundaries.
	//
	// Body source rules (role-driven — what the node *means* by "the entity"):
	//   - process_entry / process_step  -> response body. The connector just
	//     PRODUCED this data (think: input connector fetched a record from an
	//     external API; the request body is usually empty or just a cron tick).
	//   - process_exit                  -> request body. The connector tried to
	//     DELIVER this data to an external system; the response is whatever the
	//     remote returned (often just an ACK / id).
	//
	// Batch nodes are special: a single bridge call returns N items that are
	// fanned out one-by-one to followers. Emitting one audit log line for the
	// whole batch would lose per-item correlation, so we skip single-shot
	// emission here and let `Batch.AfterProcess` emit one audit per child
	// message (with each child's body as the payload). The header is kept on
	// the dto so AfterProcess can read it; it is then stripped from each
	// published partial so it doesn't leak downstream.
	//
	// Failures (invalid header, missing fields, build errors) downgrade to
	// WARN with no audit log emission — never silently log "everything".
	if rawSpec := dto.GetHeaderOrDefault(enum.Header_AuditCheckpoint, ""); rawSpec != "" {
		if node.WorkerType() == enum.WorkerType_Batch {
			// Per-item audit fan-out happens in Batch.AfterProcess. Leave the
			// header on the dto for AfterProcess to consume.
		} else if spec, err := audit.Parse(rawSpec); err != nil {
			log.Warn().EmbedObject(dto).Err(err).Msg("audit checkpoint header parse failed; skipping emission")
			dto.DeleteHeader(enum.Header_AuditCheckpoint)
		} else {
			if spec != nil {
				auditBody := auditBodyForRole(spec.Role, dto)
				payload, truncated, perr := audit.BuildPayload(auditBody, spec)
				if perr != nil {
					log.Warn().EmbedObject(dto).Err(perr).Msg("audit checkpoint payload build failed; skipping emission")
				} else {
					audit.Emit(dto, node, spec, payload, truncated, audit.EmitParams{
						ResultCode:    dto.GetIntHeaderOrDefault(enum.Header_ResultCode, 0),
						ResultMessage: dto.GetHeaderOrDefault(enum.Header_ResultMessage, ""),
						HTTPStatus:    response.StatusCode,
					})
				}
			}
			// One-shot header — strip so it doesn't leak into downstream messages.
			dto.DeleteHeader(enum.Header_AuditCheckpoint)
		}
	}

	if response.StatusCode > 500 {
		RecordFailure(host, nodeId, correlationId)
		if IsPoisoned(host, nodeId, correlationId) {
			log.Warn().EmbedObject(dto).
				Bool(enum.LogHeader_IsForUi, true).
				Msgf("Worker %s returned %d, poisoning correlationId %s after %d failures", host, response.StatusCode, correlationId, config.App.WorkerMaxFailures)
			return dto.Trash(fmt.Errorf("result status [%d], correlationId poisoned after %d failures", response.StatusCode, config.App.WorkerMaxFailures))
		}
		return dto.Error(fmt.Errorf("result status [%d]", response.StatusCode))
	} else if response.StatusCode >= 300 {
		return dto.Trash(
			fmt.Errorf(
				"result status [%d], message: %s",
				response.StatusCode,
				dto.GetHeaderOrDefault(enum.Header_ResultMessage, ""),
			),
		)
	}

	RecordSuccess(host, nodeId)

	if _, err := dto.GetHeader(enum.Header_ResultCode); err != nil {
		return dto.Trash(err)
	}

	resultMessage := dto.GetHeaderOrDefault(enum.Header_ResultMessage, "")
	resultCode := dto.GetIntHeaderOrDefault(enum.Header_ResultCode, 0)

	if isSuccessResultCode(resultCode) {
		log.Debug().EmbedObject(dto).
			Str(enum.LogHeader_LevelName, "debug").
			Msgf("Request successfully processed. Message: [%s]", resultMessage)
	} else {
		log.Error().EmbedObject(dto).
			Str(enum.LogHeader_LevelName, "error").
			Msgf("Request process failed. Message: [%s]", resultMessage)
	}

	return dto.Ok()
}

// auditBodyForRole picks the body that semantically represents "the entity at
// this checkpoint" depending on the audit role:
//
//   - process_exit  -> request body (what the connector tried to DELIVER to
//     the external system; the HTTP response is usually just an ACK / id).
//   - everything else (process_entry, process_step) -> response body (what the
//     connector PRODUCED; for input connectors the request is typically an
//     empty cron tick or a lookup id and the actual entity lives in the
//     response).
//
// `dto.GetOriginalBody()` returns the bytes the bridge sent to the worker
// (preserved by `FromHttpResponse` before it overwrote `Body` with the
// response). `dto.GetBody()` returns the worker's response body.
func auditBodyForRole(role string, dto *model.ProcessMessage) []byte {
	if role == "process_exit" {
		return []byte(dto.GetOriginalBody())
	}
	return dto.GetBody()
}

func isSuccessResultCode(code int) bool {
	switch code {
	case enum.ResultCode_Ok, enum.ResultCode_Repeat, enum.ResultCode_ForwardToQueue,
		enum.ResultCode_DoNotContinue, enum.ResultCode_CursorWithFollowers, enum.ResultCode_CursorOnly,
		enum.ResultCode_LimitExceeded:
		return true
	default:
		return false
	}
}
