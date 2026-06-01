package audit

import (
	"encoding/json"
	"strings"
	"testing"

	"github.com/hanaboso/pipes/bridge/pkg/enum"
	"github.com/hanaboso/pipes/bridge/pkg/model"
	"github.com/stretchr/testify/assert"
	"github.com/stretchr/testify/require"
)

// fields is a tiny helper so tests read like the SDK side.
func fields(f ...string) *[]string {
	out := append([]string{}, f...)
	return &out
}

func TestParse_Empty(t *testing.T) {
	spec, err := Parse("")
	assert.Nil(t, spec)
	assert.NoError(t, err)

	spec, err = Parse("   \n\t")
	assert.Nil(t, spec)
	assert.NoError(t, err)
}

func TestParse_RequiresFieldsAllowlist(t *testing.T) {
	_, err := Parse(`{"role":"process_entry"}`)
	require.Error(t, err)
	assert.Contains(t, err.Error(), "fields")
}

func TestParse_RejectsUnknownRole(t *testing.T) {
	_, err := Parse(`{"role":"other","fields":["id"]}`)
	require.Error(t, err)
	assert.Contains(t, err.Error(), "role")
}

func TestParse_DefaultsEntityPathToRoot(t *testing.T) {
	spec, err := Parse(`{"role":"process_entry","fields":["id"]}`)
	require.NoError(t, err)
	assert.Equal(t, "$", spec.EntityPath)
}

func TestBuildPayload_EmptyBodyReturnsEmptyMap(t *testing.T) {
	// The previous bridge bug: input/cron-triggered nodes have empty request
	// bodies, which produced a `nil` payload. Now we return an empty object so
	// the audit log signal is preserved (timestamp + correlationId + role).
	spec := &AuditSpec{Role: "process_entry", EntityPath: "$", Fields: fields("id")}
	payload, truncated, err := BuildPayload(nil, spec)
	require.NoError(t, err)
	assert.False(t, truncated)
	assert.Equal(t, map[string]any{}, payload)
}

func TestBuildPayload_MarkerModeReturnsNil(t *testing.T) {
	spec := &AuditSpec{Role: "process_step", EntityPath: "$", Fields: fields()}
	payload, truncated, err := BuildPayload([]byte(`{"id":"x"}`), spec)
	require.NoError(t, err)
	assert.False(t, truncated)
	assert.Nil(t, payload)
}

func TestBuildPayload_PicksAllowlistedFields(t *testing.T) {
	spec := &AuditSpec{Role: "process_entry", EntityPath: "$", Fields: fields("id", "name")}
	body := []byte(`{"id":"o-1","name":"Alice","secret":"shh","sku":"X"}`)
	payload, _, err := BuildPayload(body, spec)
	require.NoError(t, err)
	assert.Equal(t, map[string]any{"id": "o-1", "name": "Alice"}, payload)
}

func TestBuildPayload_RedactsSensitiveKeysEvenIfAllowlisted(t *testing.T) {
	// Defense-in-depth: developer accidentally adds a sensitive field name to
	// the allowlist; the regex still masks the value.
	spec := &AuditSpec{Role: "process_entry", EntityPath: "$", Fields: fields("id", "api_key")}
	body := []byte(`{"id":"x","api_key":"AKIA..."}`)
	payload, _, err := BuildPayload(body, spec)
	require.NoError(t, err)
	m, ok := payload.(map[string]any)
	require.True(t, ok)
	assert.Equal(t, "x", m["id"])
	assert.Equal(t, redactedPlaceholder, m["api_key"])
}

func TestBuildPayload_ResolvesEntityPath(t *testing.T) {
	spec := &AuditSpec{Role: "process_step", EntityPath: "$.order", Fields: fields("id")}
	body := []byte(`{"order":{"id":"o-1","total":100},"meta":{"x":1}}`)
	payload, _, err := BuildPayload(body, spec)
	require.NoError(t, err)
	assert.Equal(t, map[string]any{"id": "o-1"}, payload)
}

func TestBuildPayload_SupportsArrayTarget(t *testing.T) {
	// Batch checkpoints can declare a root-level array (or the body itself can
	// be an array of items). The allowlist is applied to each element.
	spec := &AuditSpec{Role: "process_entry", EntityPath: "$", Fields: fields("id")}
	body := []byte(`[{"id":"a","secret":"s"},{"id":"b","secret":"s"}]`)
	payload, _, err := BuildPayload(body, spec)
	require.NoError(t, err)
	arr, ok := payload.([]any)
	require.True(t, ok)
	assert.Len(t, arr, 2)
	assert.Equal(t, map[string]any{"id": "a"}, arr[0])
	assert.Equal(t, map[string]any{"id": "b"}, arr[1])
}

const postBody = `{
	"title": "t",
	"user": {"email": "u@x"},
	"comments": [
		{"content": "c1", "user": {"email": "a@x"}},
		{"content": "c2", "user": {"email": "b@y"}}
	]
}`

func TestBuildPayload_ArrayLeafFieldProjectsEachElement(t *testing.T) {
	spec := &AuditSpec{Role: "process_exit", EntityPath: "$", Fields: fields("comments.content")}
	payload, _, err := BuildPayload([]byte(postBody), spec)
	require.NoError(t, err)
	assert.Equal(t, map[string]any{
		"comments": []any{
			map[string]any{"content": "c1"},
			map[string]any{"content": "c2"},
		},
	}, payload)
}

func TestBuildPayload_ArrayNestedLeafField(t *testing.T) {
	spec := &AuditSpec{Role: "process_exit", EntityPath: "$", Fields: fields("comments.user.email")}
	payload, _, err := BuildPayload([]byte(postBody), spec)
	require.NoError(t, err)
	assert.Equal(t, map[string]any{
		"comments": []any{
			map[string]any{"user": map[string]any{"email": "a@x"}},
			map[string]any{"user": map[string]any{"email": "b@y"}},
		},
	}, payload)
}

func TestBuildPayload_ArrayWholeSubObjectField(t *testing.T) {
	spec := &AuditSpec{Role: "process_exit", EntityPath: "$", Fields: fields("comments.user")}
	payload, _, err := BuildPayload([]byte(postBody), spec)
	require.NoError(t, err)
	assert.Equal(t, map[string]any{
		"comments": []any{
			map[string]any{"user": map[string]any{"email": "a@x"}},
			map[string]any{"user": map[string]any{"email": "b@y"}},
		},
	}, payload)
}

func TestBuildPayload_MergesFieldsSharingArrayPrefix(t *testing.T) {
	spec := &AuditSpec{
		Role:       "process_exit",
		EntityPath: "$",
		Fields:     fields("title", "user.email", "comments.content", "comments.user.email"),
	}
	payload, _, err := BuildPayload([]byte(postBody), spec)
	require.NoError(t, err)
	assert.Equal(t, map[string]any{
		"title": "t",
		"user":  map[string]any{"email": "u@x"},
		"comments": []any{
			map[string]any{"content": "c1", "user": map[string]any{"email": "a@x"}},
			map[string]any{"content": "c2", "user": map[string]any{"email": "b@y"}},
		},
	}, payload)
}

func TestBuildPayload_WholeArrayFieldUnchanged(t *testing.T) {
	spec := &AuditSpec{Role: "process_exit", EntityPath: "$", Fields: fields("comments")}
	payload, _, err := BuildPayload([]byte(postBody), spec)
	require.NoError(t, err)
	assert.Equal(t, map[string]any{
		"comments": []any{
			map[string]any{"content": "c1", "user": map[string]any{"email": "a@x"}},
			map[string]any{"content": "c2", "user": map[string]any{"email": "b@y"}},
		},
	}, payload)
}

func TestBuildPayload_HeterogeneousArrayKeepsIndexAlignment(t *testing.T) {
	body := []byte(`{"comments":[{"content":"c1"},{"other":"x"}]}`)
	spec := &AuditSpec{Role: "process_exit", EntityPath: "$", Fields: fields("comments.content")}
	payload, _, err := BuildPayload(body, spec)
	require.NoError(t, err)
	assert.Equal(t, map[string]any{
		"comments": []any{
			map[string]any{"content": "c1"},
			map[string]any{},
		},
	}, payload)
}

func TestBuildPayload_ArrayCrossingFieldRedacted(t *testing.T) {
	body := []byte(`{"comments":[{"token":"AKIA","content":"c1"}]}`)
	spec := &AuditSpec{Role: "process_exit", EntityPath: "$", Fields: fields("comments.token", "comments.content")}
	payload, _, err := BuildPayload(body, spec)
	require.NoError(t, err)
	arr := payload.(map[string]any)["comments"].([]any)
	require.Len(t, arr, 1)
	assert.Equal(t, redactedPlaceholder, arr[0].(map[string]any)["token"])
	assert.Equal(t, "c1", arr[0].(map[string]any)["content"])
}

func TestBuildPayload_InvalidJsonFallsBackToBase64(t *testing.T) {
	spec := &AuditSpec{Role: "process_step", EntityPath: "$", Fields: fields("id")}
	payload, _, err := BuildPayload([]byte("not-json-at-all"), spec)
	require.NoError(t, err)
	m, ok := payload.(map[string]any)
	require.True(t, ok)
	assert.Equal(t, true, m["_invalidJson"])
	assert.NotEmpty(t, m["_base64"])
}

func TestBuildPayload_TruncatesOversizedPayload(t *testing.T) {
	spec := &AuditSpec{Role: "process_step", EntityPath: "$", Fields: fields("blob")}
	huge := strings.Repeat("x", MaxAuditPayloadBytes+10)
	body, _ := json.Marshal(map[string]string{"blob": huge})
	payload, truncated, err := BuildPayload(body, spec)
	require.NoError(t, err)
	assert.True(t, truncated)
	m, ok := payload.(map[string]any)
	require.True(t, ok)
	assert.Equal(t, true, m["_truncated"])
}

func TestBuildPayload_UnresolvedPathReturnsEmptyMap(t *testing.T) {
	spec := &AuditSpec{Role: "process_step", EntityPath: "$.missing", Fields: fields("id")}
	body := []byte(`{"order":{"id":"x"}}`)
	payload, _, err := BuildPayload(body, spec)
	require.NoError(t, err)
	assert.Equal(t, map[string]any{}, payload)
}

func TestBuildAuditLine(t *testing.T) {
	dto := (&model.ProcessMessage{}).
		SetHeader(enum.Header_CorrelationId, "corr-1").
		SetHeader(enum.Header_TopologyId, "topo-1").
		SetHeader(enum.Header_NodeName, "beeceptor-sync-post-connector")

	cp := map[string]any{
		"role":         "process_exit",
		"resultStatus": "success",
		"httpStatus":   200,
		"payload":      map[string]any{"title": "t"},
	}

	labels, line := buildAuditLine(dto, cp, "json-placeholder-to-beeceptor-posts")

	assert.Equal(t, map[string]string{
		"correlationId": "corr-1",
		"levelName":     "info",
		"topologyId":    "topo-1",
	}, labels)

	assert.NotContains(t, string(line), "\n")
	var parsed map[string]any
	require.NoError(t, json.Unmarshal(line, &parsed))
	assert.Equal(t, "corr-1", parsed["correlationId"])
	assert.Equal(t, "topo-1", parsed["topologyId"])
	assert.Equal(t, "info", parsed["levelName"])
	assert.Equal(t, "beeceptor-sync-post-connector", parsed["nodeName"])
	assert.Equal(t, "json-placeholder-to-beeceptor-posts", parsed["topologyName"])
	assert.Equal(t, "Audit checkpoint", parsed["message"])

	ac, ok := parsed["auditCheckpoint"].(map[string]any)
	require.True(t, ok)
	assert.Equal(t, "process_exit", ac["role"])
	assert.Equal(t, "success", ac["resultStatus"])
}

func TestClassifyStatus(t *testing.T) {
	cases := []struct {
		name       string
		resultCode int
		httpStatus int
		want       string
	}{
		{"5xx -> failed regardless of resultCode", 0, 502, AuditStatusFailed},
		{"4xx -> trashed", 0, 404, AuditStatusTrashed},
		{"ok -> success", 0, 200, AuditStatusSuccess},
		{"repeat -> repeat", 1001, 200, AuditStatusRepeat},
		{"limit -> limit", 1004, 200, AuditStatusLimit},
		{"stop and fail -> failed", 1006, 200, AuditStatusFailed},
		{"unknown code -> unknown", 9999, 200, AuditStatusUnknown},
	}
	for _, c := range cases {
		t.Run(c.name, func(t *testing.T) {
			assert.Equal(t, c.want, ClassifyStatus(c.resultCode, c.httpStatus))
		})
	}
}

func TestSanitizeHeaders(t *testing.T) {
	out := SanitizeHeaders(map[string]any{
		"Authorization":  "Bearer abc",
		"Cookie":         "x=1",
		"X-Trace-Id":     "t-1",
		"X-Custom-Token": "t",
	})
	assert.Equal(t, redactedPlaceholder, out["Authorization"])
	assert.Equal(t, redactedPlaceholder, out["Cookie"])
	assert.Equal(t, "t-1", out["X-Trace-Id"])
	assert.Equal(t, redactedPlaceholder, out["X-Custom-Token"])
}
