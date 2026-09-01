package model

import (
	"io"
	"net/http"
	"strings"
	"testing"

	"github.com/hanaboso/pipes/bridge/pkg/enum"
	"github.com/stretchr/testify/assert"
)

func httpResponse(body string) *http.Response {
	return &http.Response{
		Body:       io.NopCloser(strings.NewReader(body)),
		StatusCode: 200,
	}
}

func TestProcessDto_GetHeader(t *testing.T) {
	dto := prepareDto()
	value, err := dto.GetHeader("string")
	assert.Nil(t, err)
	assert.Equal(t, "string", value)

	value, err = dto.GetHeader("losos")
	assert.NotNil(t, err)
	assert.Equal(t, "", value)
}

func TestProcessDto_GetHeaderOrDefault(t *testing.T) {
	dto := prepareDto()
	value := dto.GetHeaderOrDefault("string", "asd")
	assert.Equal(t, "string", value)

	value = dto.GetHeaderOrDefault("losos", "asd")
	assert.Equal(t, "asd", value)
}

func TestProcessDto_GetIntHeader(t *testing.T) {
	dto := prepareDto()
	value, err := dto.GetIntHeader("int")
	assert.Nil(t, err)
	assert.Equal(t, 666, value)

	value, err = dto.GetIntHeader("losos")
	assert.NotNil(t, err)
	assert.Equal(t, 0, value)
}

func TestProcessDto_GetIntHeaderOrDefault(t *testing.T) {
	dto := prepareDto()
	value := dto.GetIntHeaderOrDefault("int", 5)
	assert.Equal(t, 666, value)

	value = dto.GetIntHeaderOrDefault("losos", 5)
	assert.Equal(t, 5, value)
}

func TestProcessDto_SetHeader(t *testing.T) {
	dto := prepareDto()
	dto.SetHeader("a", "a")

	value, err := dto.GetHeader("a")
	assert.Nil(t, err)
	assert.Equal(t, "a", value)
}

func prepareDto() ProcessMessage {
	dto := ProcessMessage{}
	dto.SetHeader("string", "string")
	dto.SetHeader("int", "666")

	return dto
}

func TestProcessDto_FromHttpResponse_PartialEchoPreservesIdentity(t *testing.T) {
	dto := &ProcessMessage{}
	dto.SetHeader(enum.Header_CorrelationId, "corr-1")
	dto.SetHeader(enum.Header_ProcessId, "proc-1")
	dto.SetHeader(enum.Header_NodeId, "node-1")
	dto.SetHeader(enum.Header_TopologyId, "topo-1")

	err := dto.FromHttpResponse(httpResponse(`{"headers":{"result-code":"0"},"body":"{}"}`))
	assert.Nil(t, err)

	assert.Equal(t, "corr-1", dto.Headers[enum.Header_CorrelationId])
	assert.Equal(t, "proc-1", dto.Headers[enum.Header_ProcessId])
	assert.Equal(t, "node-1", dto.Headers[enum.Header_NodeId])
	assert.Equal(t, "topo-1", dto.Headers[enum.Header_TopologyId])
	assert.Equal(t, "0", dto.Headers["result-code"])
}

func TestProcessDto_FromHttpResponse_IdentityWinsOverWorker(t *testing.T) {
	dto := &ProcessMessage{}
	dto.SetHeader(enum.Header_CorrelationId, "corr-bridge")

	err := dto.FromHttpResponse(httpResponse(`{"headers":{"correlation-id":"corr-worker"},"body":"{}"}`))
	assert.Nil(t, err)

	assert.Equal(t, "corr-bridge", dto.Headers[enum.Header_CorrelationId])
}

func TestProcessDto_FromHttpResponse_EmptyBodyErrorsNoMutation(t *testing.T) {
	dto := &ProcessMessage{Body: []byte("original")}
	dto.SetHeader(enum.Header_CorrelationId, "corr-1")

	err := dto.FromHttpResponse(httpResponse("   "))
	assert.NotNil(t, err)

	assert.Equal(t, []byte("original"), dto.Body)
	assert.Equal(t, "corr-1", dto.Headers[enum.Header_CorrelationId])
}

func TestProcessDto_FromHttpResponse_NonJSONErrorsNoMutation(t *testing.T) {
	dto := &ProcessMessage{Body: []byte("original")}
	dto.SetHeader(enum.Header_CorrelationId, "corr-1")

	err := dto.FromHttpResponse(httpResponse("<html>503</html>"))
	assert.NotNil(t, err)

	assert.Equal(t, []byte("original"), dto.Body)
	assert.Equal(t, "corr-1", dto.Headers[enum.Header_CorrelationId])
}

func TestProcessDto_FromHttpResponse_ValidEnvelope(t *testing.T) {
	dto := &ProcessMessage{Body: []byte("original")}
	dto.KeepRepeatHeaders = true

	err := dto.FromHttpResponse(httpResponse(`{"headers":{"result-code":"0"},"body":"new-body"}`))
	assert.Nil(t, err)

	assert.Equal(t, []byte("new-body"), dto.Body)
	assert.Equal(t, []byte("original"), dto.bodyBackup)
	assert.False(t, dto.KeepRepeatHeaders)
	assert.Equal(t, "0", dto.Headers["result-code"])
}

func TestProcessDto_CopyBatchItem_StripsParentAuditHeaders(t *testing.T) {
	parent := &ProcessMessage{
		Headers: map[string]interface{}{
			"correlation-id":                    "corr-1",
			"node-id":                           "node-1",
			enum.Header_AuditEntityHeader:       `{"order":{"key":"id","fields":[{"id":"ord-001"},{"id":"ord-002"}]}}`,
			enum.Header_AuditEntityIdsHeader:    "order:ord-001:audit-1,order:ord-002:audit-2",
			enum.Header_AuditEntityFieldsHeader: "{}",
		},
	}

	itemAuditEntity := `{"order":{"key":"id","fields":[{"id":"ord-001"}]}}`
	item := MessageDto{
		Headers: map[string]interface{}{
			enum.Header_AuditEntityHeader: itemAuditEntity,
		},
		Body: `{"id":"ord-001"}`,
	}

	child := parent.CopyBatchItem(item)

	// Parent shared context (correlation-id, node-id) must be carried over.
	assert.Equal(t, "corr-1", child.Headers["correlation-id"])
	assert.Equal(t, "node-1", child.Headers["node-id"])

	// Parent audit headers must NOT be propagated to the child.
	_, hasIds := child.Headers[enum.Header_AuditEntityIdsHeader]
	assert.False(t, hasIds, "parent audit-entity-ids must not leak into child message")
	_, hasFields := child.Headers[enum.Header_AuditEntityFieldsHeader]
	assert.False(t, hasFields, "parent audit-entity-fields must not leak into child message")

	// The per-item audit-entity (set via item.Headers) must be the only one present.
	assert.Equal(t, itemAuditEntity, child.Headers[enum.Header_AuditEntityHeader])

	// Body must be the per-item body.
	assert.Equal(t, []byte(`{"id":"ord-001"}`), child.Body)
}

func TestProcessDto_CopyBatchItem_NoItemAuditMeansNoAudit(t *testing.T) {
	parent := &ProcessMessage{
		Headers: map[string]interface{}{
			enum.Header_AuditEntityHeader:    `{"order":{"key":"id","fields":[{"id":"ord-001"}]}}`,
			enum.Header_AuditEntityIdsHeader: "order:ord-001:audit-1",
		},
	}

	item := MessageDto{
		Headers: map[string]interface{}{},
		Body:    `{"id":"ord-001"}`,
	}

	child := parent.CopyBatchItem(item)

	_, hasEntity := child.Headers[enum.Header_AuditEntityHeader]
	assert.False(t, hasEntity, "if neither parent (stripped) nor item supplies audit-entity, child has none")
	_, hasIds := child.Headers[enum.Header_AuditEntityIdsHeader]
	assert.False(t, hasIds, "audit-entity-ids must not leak from parent")
}
