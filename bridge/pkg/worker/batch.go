package worker

import (
	"encoding/json"

	"github.com/hanaboso/pipes/bridge/pkg/audit"
	"github.com/hanaboso/pipes/bridge/pkg/bridge/types"
	"github.com/hanaboso/pipes/bridge/pkg/enum"
	"github.com/hanaboso/pipes/bridge/pkg/model"
	"github.com/rs/zerolog/log"
)

type Batch struct {
	httpBeforeProcess
}

func (Batch) AfterProcess(node types.Node, dto *model.ProcessMessage) (model.ProcessResult, int) {
	published := 0

	var contents []model.MessageDto
	err := json.Unmarshal(dto.GetBody(), &contents)
	if err != nil {
		return dto.Trash(err), 0
	}

	resultCode := dto.GetIntHeaderOrDefault(enum.Header_ResultCode, 0)
	parentProcessId, _ := dto.GetHeader(enum.Header_ProcessId)

	// Audit fan-out: a batch declaring `audit-checkpoint` produces one audit
	// log line PER EMITTED ITEM, built from the item's own body. The batch
	// trigger payload (typically an empty cron tick) is intentionally NOT
	// used — for input batches it's empty and would yield empty audit logs;
	// for derived/lookup batches the actual entity lives in the items, not
	// in the upstream payload. See worker/http.go for the matching skip.
	auditSpec := parseBatchAuditSpec(dto)

	if resultCode != enum.ResultCode_CursorOnly {
		for _, content := range contents {
			emitBatchItemAudit(dto, node, content, auditSpec, parentProcessId, resultCode)

			for _, publisher := range node.Followers() {
				published++
				partial := dto.
					CopyBatchItem(content).
					SetHeader(enum.Header_ParentProcessId, parentProcessId).
					SetHeader(enum.Header_ProcessId, newUuid()).
					SetHeader(enum.Header_PreviousNodeId, node.Id()).
					DeleteHeader(enum.Header_Cursor).
					DeleteHeader(enum.Header_AuditCheckpoint)
				if err := publisher.Publish(partial.IntoAmqp()); err != nil {
					return dto.Error(err), 0
				}
			}
		}
	}

	if resultCode == enum.ResultCode_CursorWithFollowers || resultCode == enum.ResultCode_CursorOnly {
		published++
		if err := node.CursorPublisher().Publish(dto.IntoOriginalAmqp()); err != nil {
			return dto.Error(err), 0
		}
	}

	return dto.Ok(), published
}

// parseBatchAuditSpec reads the `audit-checkpoint` header from a batch dto and
// returns the parsed spec, or nil if absent / invalid (with a WARN log so the
// developer notices). Returning nil here disables audit fan-out — we never
// silently fall back to "log everything".
func parseBatchAuditSpec(dto *model.ProcessMessage) *audit.AuditSpec {
	rawSpec := dto.GetHeaderOrDefault(enum.Header_AuditCheckpoint, "")
	if rawSpec == "" {
		return nil
	}
	spec, err := audit.Parse(rawSpec)
	if err != nil {
		log.Warn().EmbedObject(dto).Err(err).Msg("audit checkpoint header parse failed for batch; skipping per-item emission")
		return nil
	}
	return spec
}

// emitBatchItemAudit writes one audit log line for a single child message
// produced by the batch. The payload is built from the child's body so it
// reflects the actual entity flowing downstream — not the (often empty) batch
// trigger payload. The synthetic child dto inherits the parent's
// correlation/topology headers (so trace queries by correlationId still work)
// and gets a fresh ProcessId representing "this item".
//
// HTTP status is hard-coded to 200 because the batch worker call already
// succeeded by the time AfterProcess runs (BeforeProcess would have trashed /
// errored the dto otherwise). resultCode reflects the batch outcome.
func emitBatchItemAudit(
	parent *model.ProcessMessage,
	node types.Node,
	item model.MessageDto,
	spec *audit.AuditSpec,
	parentProcessId string,
	resultCode int,
) {
	if spec == nil {
		return
	}

	auditChild := parent.
		CopyBatchItem(item).
		SetHeader(enum.Header_ParentProcessId, parentProcessId).
		SetHeader(enum.Header_ProcessId, newUuid()).
		SetHeader(enum.Header_PreviousNodeId, node.Id()).
		DeleteHeader(enum.Header_AuditCheckpoint)

	payload, truncated, perr := audit.BuildPayload([]byte(item.Body), spec)
	if perr != nil {
		log.Warn().EmbedObject(auditChild).Err(perr).Msg("audit checkpoint payload build failed for batch item; skipping emission")
		return
	}

	audit.Emit(auditChild, node, spec, payload, truncated, audit.EmitParams{
		ResultCode: resultCode,
		HTTPStatus: 200,
	})
}
