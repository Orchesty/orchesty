package worker

import (
	"bytes"
	"strconv"
	"strings"
	"testing"

	"github.com/hanaboso/pipes/bridge/pkg/enum"
	"github.com/rs/zerolog"
	"github.com/rs/zerolog/log"
	"github.com/stretchr/testify/assert"
)

func TestBatch_AfterProcess(t *testing.T) {
	worker := Batch{}
	dto := prepDto()
	dto.Body = []byte("[{\"body\":\"a\",\"headers\":null}]")
	_, p := worker.AfterProcess(nullNode{}, dto)

	assert.Equal(t, 3, p)
}

func TestBatch_AfterProcessCursor(t *testing.T) {
	worker := Batch{}
	dto := prepDto()
	dto.Body = []byte("[{\"body\":\"a\",\"headers\":null}]")
	dto.SetHeader(enum.Header_ResultCode, strconv.Itoa(enum.ResultCode_CursorWithFollowers))
	_, p := worker.AfterProcess(nullNode{}, dto)

	assert.Equal(t, 4, p)
}

func TestBatch_AfterProcessCursorOnly(t *testing.T) {
	worker := Batch{}
	dto := prepDto()
	dto.Body = []byte("[{\"body\":\"a\",\"headers\":null}]")
	dto.SetHeader(enum.Header_ResultCode, strconv.Itoa(enum.ResultCode_CursorOnly))
	_, p := worker.AfterProcess(nullNode{}, dto)

	assert.Equal(t, 1, p)
}

// captureLogs swaps the global zerolog logger with a buffer-backed one for the
// duration of fn. Returns the line-by-line output. Used to assert audit
// emission without coupling tests to a specific transport.
func captureLogs(t *testing.T, fn func()) []string {
	t.Helper()
	var buf bytes.Buffer
	original := log.Logger
	log.Logger = zerolog.New(&buf)
	defer func() { log.Logger = original }()

	fn()

	raw := strings.TrimRight(buf.String(), "\n")
	if raw == "" {
		return nil
	}
	return strings.Split(raw, "\n")
}

// TestBatch_AfterProcess_NoAuditWhenHeaderAbsent guards the existing
// (non-audit) batch path: we must not emit audit logs for batches that do not
// declare an audit-checkpoint.
func TestBatch_AfterProcess_NoAuditWhenHeaderAbsent(t *testing.T) {
	worker := Batch{}

	logs := captureLogs(t, func() {
		dto := prepDto()
		dto.Body = []byte(`[{"body":"{\"id\":\"a\"}","headers":null}]`)
		_, _ = worker.AfterProcess(nullNode{}, dto)
	})

	for _, line := range logs {
		assert.NotContains(t, line, "Audit checkpoint", "no audit lines expected when header is absent")
	}
}

// TestBatch_AfterProcess_FanOutAuditPerItem verifies the new behaviour: a
// batch declaring an audit-checkpoint emits ONE audit log line PER ITEM, with
// each line built from the item's own body — not from the (here empty) batch
// trigger payload.
func TestBatch_AfterProcess_FanOutAuditPerItem(t *testing.T) {
	worker := Batch{}

	// Three nullNode followers => 3 published items per content; ensure we
	// still see 2 audit lines (one per CONTENT, not per delivery).
	body := `[` +
		`{"body":"{\"id\":\"o-1\",\"name\":\"Alice\",\"secret\":\"s\"}","headers":null},` +
		`{"body":"{\"id\":\"o-2\",\"name\":\"Bob\",\"secret\":\"s\"}","headers":null}` +
		`]`

	logs := captureLogs(t, func() {
		dto := prepDto()
		dto.Body = []byte(body)
		// Trigger payload (what the batch RECEIVED) is intentionally empty —
		// this is the cron-tick scenario. Audit must come from the items.
		// (prepDto already leaves Body to be set by the test; bodyBackup is
		// empty so GetOriginalBody() == "" here.)
		dto.SetHeader(enum.Header_AuditCheckpoint, `{"role":"process_entry","fields":["id","name"]}`)
		_, _ = worker.AfterProcess(nullNode{}, dto)
	})

	auditLines := 0
	for _, line := range logs {
		if !strings.Contains(line, "Audit checkpoint") {
			continue
		}
		auditLines++
		// Per-item payload assertions: each item's id/name must appear in
		// SOME audit line; the secret field must NEVER appear.
		assert.NotContains(t, line, `"secret"`, "secret field must not leak into audit log")
	}
	assert.Equal(t, 2, auditLines, "expected one audit line per item, got %d", auditLines)

	// Spot-check the per-item payloads landed in the captured output (order
	// in the loop is deterministic).
	joined := strings.Join(logs, "\n")
	assert.Contains(t, joined, `"id":"o-1"`)
	assert.Contains(t, joined, `"name":"Alice"`)
	assert.Contains(t, joined, `"id":"o-2"`)
	assert.Contains(t, joined, `"name":"Bob"`)
}

// TestBatch_AfterProcess_InvalidAuditHeaderSkipsEmission ensures malformed
// audit-checkpoint headers downgrade to a WARN with NO audit log emission
// (never silently fall back to "log everything").
func TestBatch_AfterProcess_InvalidAuditHeaderSkipsEmission(t *testing.T) {
	worker := Batch{}

	logs := captureLogs(t, func() {
		dto := prepDto()
		dto.Body = []byte(`[{"body":"{\"id\":\"a\"}","headers":null}]`)
		// Missing required `fields` allowlist -> Parse rejects.
		dto.SetHeader(enum.Header_AuditCheckpoint, `{"role":"process_entry"}`)
		_, _ = worker.AfterProcess(nullNode{}, dto)
	})

	for _, line := range logs {
		assert.NotContains(t, line, "Audit checkpoint", "must not emit audit lines for invalid spec")
	}
	// And we must have logged the warn so the developer notices.
	joined := strings.Join(logs, "\n")
	assert.Contains(t, joined, "audit checkpoint header parse failed")
}

// TestBatch_AfterProcess_AuditHeaderStrippedFromPublishedMessages ensures the
// audit-checkpoint header is removed from published partials so it does not
// leak into downstream nodes (which would otherwise re-emit duplicate audits
// against the wrong node context).
func TestBatch_AfterProcess_AuditHeaderStrippedFromPublishedMessages(t *testing.T) {
	worker := Batch{}
	captured := newCapturingNode()

	dto := prepDto()
	dto.Body = []byte(`[{"body":"{\"id\":\"a\"}","headers":null}]`)
	dto.SetHeader(enum.Header_AuditCheckpoint, `{"role":"process_entry","fields":["id"]}`)
	_, _ = worker.AfterProcess(captured, dto)

	require := captured.publisher.headers
	for i, headers := range require {
		_, hasCheckpoint := headers[enum.Header_AuditCheckpoint]
		assert.False(t, hasCheckpoint, "audit-checkpoint header leaked into published partial #%d", i)
	}
}
