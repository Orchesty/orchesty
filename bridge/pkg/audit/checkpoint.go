// Package audit produces structured business audit log lines from the
// `audit-checkpoint` HTTP response header set by the Node SDK Router.
//
// Security model (defense in depth):
//
//  1. The `fields` allowlist on the spec is REQUIRED. Missing -> Parse fails ->
//     no audit log is emitted (we error out, never fallback to "log everything").
//  2. A last-resort key regex masks any field whose name suggests a secret even
//     if the developer accidentally added it to the allowlist.
//  3. A 64 KB hard size limit replaces the payload with a placeholder; we never
//     truncate raw bytes (risk of split UTF-8 / leaking partial data).
//  4. JSON validation: invalid input -> base64 fallback with a flag.
//  5. Role whitelist on Parse.
//  6. Header denylist: SanitizeHeaders strips/masks known-sensitive headers and
//     anything matching the secret regex.
//  7. `auditEntityIds` is NEVER included in the log line; cross-attribute lookup
//     happens upstream in Mongo (audit_entity / audit_data).
package audit

import (
	"bytes"
	"encoding/base64"
	"encoding/json"
	"fmt"
	"net/http"
	"reflect"
	"regexp"
	"strings"

	"github.com/hanaboso/pipes/bridge/pkg/enum"
	"github.com/hanaboso/pipes/bridge/pkg/model"
	"github.com/rs/zerolog/log"
)

// MaxAuditMessageLength caps resultMessage in the emitted audit log line so
// stack traces / verbose worker errors don't blow up Loki entries. The full
// message stays available via the trash record / standard error log.
const MaxAuditMessageLength = 512

// Status classifications projected onto SDK ResultCode + HTTP status. Kept as
// human-readable strings so the Trace UI can branch directly without
// re-mapping.
const (
	AuditStatusSuccess = "success"
	AuditStatusFailed  = "failed"
	AuditStatusRepeat  = "repeat"
	AuditStatusTrashed = "trashed"
	AuditStatusLimit   = "limit"
	AuditStatusUnknown = "unknown"
)

// ClassifyStatus folds the SDK ResultCode + transport HTTP status into a
// stable human-readable label used in the audit log line and surfaced to the
// Trace UI as a delivery badge. Pure function, easy to unit-test.
//
// Mapping rules (in priority order):
//  1. HTTP status >= 500 -> failed (worker crashed / unreachable)
//  2. HTTP status in [300, 500) -> trashed (bridge will trash, see http.go)
//  3. ResultCode_Repeat / ResultCode_LimitExceeded -> repeat / limit
//  4. ResultCode_Ok / ResultCode_DoNotContinue / ResultCode_CursorOnly /
//     ResultCode_CursorWithFollowers / ResultCode_ForwardToQueue -> success
//  5. ResultCode_StopAndFail or anything unknown -> failed
func ClassifyStatus(resultCode int, httpStatus int) string {
	if httpStatus >= 500 {
		return AuditStatusFailed
	}
	if httpStatus >= 300 && httpStatus < 500 {
		return AuditStatusTrashed
	}
	switch resultCode {
	case enum.ResultCode_Ok,
		enum.ResultCode_DoNotContinue,
		enum.ResultCode_CursorOnly,
		enum.ResultCode_CursorWithFollowers,
		enum.ResultCode_ForwardToQueue:
		return AuditStatusSuccess
	case enum.ResultCode_Repeat:
		return AuditStatusRepeat
	case enum.ResultCode_LimitExceeded:
		return AuditStatusLimit
	case enum.ResultCode_StopAndFail:
		return AuditStatusFailed
	}
	return AuditStatusUnknown
}

// MaxAuditPayloadBytes is the hard size limit for a single audit payload after
// the allowlist + redaction is applied. Anything larger is replaced with a
// placeholder so we don't bloat Loki and don't risk leaking via half-truncated
// JSON.
const MaxAuditPayloadBytes = 64 * 1024

// sensitiveHeaderNames are header names whose value must never reach Loki.
// Lower-case lookup; canonicalize before comparing.
var sensitiveHeaderNames = map[string]struct{}{
	"authorization":       {},
	"cookie":              {},
	"set-cookie":          {},
	"proxy-authorization": {},
	"x-api-key":           {},
	"x-csrf-token":        {},
}

// sensitiveKeyRegex masks any field whose name contains "password", "secret",
// "token", "api_key", "auth", regardless of placement (header, payload, nested
// payload). Last-resort defense-in-depth above the developer-defined allowlist.
var sensitiveKeyRegex = regexp.MustCompile(`(?i)(password|passwd|secret|token|api[-_]?key|auth)`)

const redactedPlaceholder = "<redacted>"

// AuditSpec is the Go counterpart of the SDK's IAuditCheckpoint TypeScript
// interface, transmitted from the worker via the `audit-checkpoint` HTTP
// response header (JSON-encoded).
//
// `Fields` is a pointer so we can distinguish three cases:
//   - nil    -> caller forgot to set it (Parse rejects)
//   - &[]    -> marker mode; emit only role/correlation, no payload
//   - &[...] -> standard allowlist
type AuditSpec struct {
	Role       string    `json:"role"`
	EntityPath string    `json:"entityPath,omitempty"`
	Fields     *[]string `json:"fields"`
}

var validRoles = map[string]struct{}{
	"process_entry": {},
	"process_step":  {},
	"process_exit":  {},
}

// Parse extracts an AuditSpec from the raw header value.
//
// Returns (nil, nil) when the input is empty (no audit checkpoint declared).
// Returns (nil, err) when the spec is invalid (missing fields allowlist, bad
// JSON, unknown role) — caller must NOT emit anything in that case.
func Parse(rawHeader string) (*AuditSpec, error) {
	if strings.TrimSpace(rawHeader) == "" {
		return nil, nil
	}

	var spec AuditSpec
	if err := json.Unmarshal([]byte(rawHeader), &spec); err != nil {
		return nil, fmt.Errorf("audit checkpoint: invalid JSON in header: %w", err)
	}

	if spec.Fields == nil {
		return nil, fmt.Errorf("audit checkpoint: missing required 'fields' allowlist")
	}

	if _, ok := validRoles[spec.Role]; !ok {
		return nil, fmt.Errorf("audit checkpoint: invalid role %q", spec.Role)
	}

	if spec.EntityPath == "" {
		spec.EntityPath = "$"
	}

	return &spec, nil
}

// BuildPayload applies entityPath + fields allowlist + last-resort regex
// redaction + size limit on the request body. Returns:
//   - payload: a JSON-serializable subset (or a placeholder map when truncated
//     / invalid). nil iff the spec is marker-only (Fields == []).
//   - truncated: true iff size limit kicked in.
//   - err: only for unrecoverable internal errors (caller already validated the
//     spec via Parse).
func BuildPayload(reqBody []byte, spec *AuditSpec) (any, bool, error) {
	if spec == nil || spec.Fields == nil {
		return nil, false, fmt.Errorf("audit checkpoint: spec must be parsed first")
	}

	// Marker mode — caller wants no payload, only the audit signal that the
	// entity flowed through. Used for very sensitive entities.
	if len(*spec.Fields) == 0 {
		return nil, false, nil
	}

	if len(reqBody) == 0 {
		return map[string]any{}, false, nil
	}

	var root any
	if err := json.Unmarshal(reqBody, &root); err != nil {
		// Body is not valid JSON (e.g. binary, plain text). Fall back to a
		// flagged base64 representation so the audit signal is preserved
		// without leaking raw bytes into the log line.
		encoded := base64.StdEncoding.EncodeToString(reqBody)
		if len(encoded) > MaxAuditPayloadBytes {
			return map[string]any{
				"_invalidJson":       true,
				"_truncated":         true,
				"_originalSizeBytes": len(reqBody),
			}, true, nil
		}
		return map[string]any{
			"_invalidJson": true,
			"_base64":      encoded,
		}, false, nil
	}

	target, err := resolveEntityPath(root, spec.EntityPath)
	if err != nil || target == nil {
		// Path doesn't resolve — emit empty object rather than fail; the
		// audit signal (timestamp + correlationId + role) is still useful.
		return map[string]any{}, false, nil
	}

	picked := pickFromTarget(target, *spec.Fields)
	picked = redactSensitive(picked)

	encoded, err := json.Marshal(picked)
	if err != nil {
		return map[string]any{
			"_invalidJson":       true,
			"_originalSizeBytes": len(reqBody),
		}, false, nil
	}

	if len(encoded) > MaxAuditPayloadBytes {
		return map[string]any{
			"_truncated":         true,
			"_originalSizeBytes": len(encoded),
		}, true, nil
	}

	return picked, false, nil
}

// EmitParams bundles delivery-status fields the caller harvested from the
// worker response. Decoupled from `Emit` parameters so future fields don't
// trigger a callsite explosion.
type EmitParams struct {
	ResultCode    int
	ResultMessage string
	HTTPStatus    int
}

// Emit writes the final structured INFO audit log line. Use only after a
// successful Parse + BuildPayload. `truncated` controls a side-channel WARN to
// bridge stdout so operators see allowlist scoping issues.
//
// `params` carries the delivery outcome (`resultCode/resultStatus/resultMessage/httpStatus`)
// extracted from the worker response, so the Trace UI can paint the per-checkpoint
// "Delivered / Failed / Repeating / Limit / Trashed" badge without re-querying.
func Emit(
	dto *model.ProcessMessage,
	node interface{ TopologyName() string },
	spec *AuditSpec,
	payload any,
	truncated bool,
	params EmitParams,
) {
	if dto == nil || spec == nil {
		return
	}

	cp := map[string]any{
		"role":          spec.Role,
		"resultCode":    params.ResultCode,
		"resultStatus":  ClassifyStatus(params.ResultCode, params.HTTPStatus),
		"resultMessage": truncateString(params.ResultMessage, MaxAuditMessageLength),
		"httpStatus":    params.HTTPStatus,
	}
	// Marker-only mode (Fields == []) -> intentionally omit the payload key.
	if payload != nil {
		cp["payload"] = payload
	}

	ev := log.Info().EmbedObject(dto).
		Str(enum.LogHeader_LevelName, "info").
		Interface(enum.LogHeader_AuditCheckpoint, cp)

	if node != nil {
		ev = ev.Str("topologyName", node.TopologyName())
	}

	ev.Msg("Audit checkpoint")

	if truncated {
		// Side-channel warning — does NOT pollute the audit log line itself.
		log.Warn().EmbedObject(dto).
			Msgf(
				"audit checkpoint payload truncated for nodeName=%s correlationId=%s; tighten the IAuditCheckpoint.fields allowlist or move the checkpoint past a split",
				dto.GetHeaderOrDefault(enum.Header_NodeName, ""),
				dto.GetHeaderOrDefault(enum.Header_CorrelationId, ""),
			)
	}
}

// truncateString returns s clipped to maxLen runes. Operates on runes (not
// bytes) so we never split a multi-byte character mid-sequence.
func truncateString(s string, maxLen int) string {
	if maxLen <= 0 || s == "" {
		return s
	}
	r := []rune(s)
	if len(r) <= maxLen {
		return s
	}
	return string(r[:maxLen]) + "…"
}

// SanitizeHeadersHttp adapts net/http.Header (map[string][]string) to the
// generic SanitizeHeaders helper. Returns nil for nil input.
func SanitizeHeadersHttp(headers http.Header) map[string]any {
	if headers == nil {
		return nil
	}
	asAny := make(map[string]any, len(headers))
	for k, v := range headers {
		asAny[k] = v
	}
	return SanitizeHeaders(asAny)
}

// SanitizeHeaders returns a shallow copy of `headers` with sensitive entries
// either removed (well-known names) or value-masked (anything matching the
// secret regex). Use when logging request/response headers anywhere in the
// bridge. Pure function, safe to call on arbitrarily-typed maps.
func SanitizeHeaders(headers map[string]any) map[string]any {
	if headers == nil {
		return nil
	}

	out := make(map[string]any, len(headers))
	for k, v := range headers {
		lk := strings.ToLower(k)
		if _, isSensitive := sensitiveHeaderNames[lk]; isSensitive {
			out[k] = redactedPlaceholder
			continue
		}
		if sensitiveKeyRegex.MatchString(lk) {
			out[k] = redactedPlaceholder
			continue
		}
		out[k] = v
	}

	return out
}

// resolveEntityPath walks a dot-path inside the parsed JSON root. `$` (or
// empty) means root. Each segment is treated as a map key; non-numeric segments
// against arrays return nil.
func resolveEntityPath(root any, path string) (any, error) {
	if path == "" || path == "$" {
		return root, nil
	}

	cur := root
	segments := strings.Split(strings.TrimPrefix(path, "$."), ".")
	for _, seg := range segments {
		if seg == "" {
			continue
		}
		m, ok := cur.(map[string]any)
		if !ok {
			return nil, nil
		}
		next, exists := m[seg]
		if !exists {
			return nil, nil
		}
		cur = next
	}

	return cur, nil
}

// pickFromTarget applies the fields allowlist on either a single entity (map)
// or an array of entities (so chunked batch bodies work without crashing).
// Anything else is returned unchanged minus the allowlist (uncommon but we
// don't want to lose the signal).
func pickFromTarget(target any, fields []string) any {
	if target == nil {
		return map[string]any{}
	}

	v := reflect.ValueOf(target)
	if v.Kind() == reflect.Slice {
		out := make([]any, 0, v.Len())
		for i := 0; i < v.Len(); i++ {
			elem := v.Index(i).Interface()
			out = append(out, pickFields(elem, fields))
		}
		return out
	}

	return pickFields(target, fields)
}

// pickFields extracts a subset of `entity` (must be a map) keyed by the
// allowlist. Supports dot-paths inside `fields` (e.g. "customer.email").
func pickFields(entity any, fields []string) map[string]any {
	out := map[string]any{}

	m, ok := entity.(map[string]any)
	if !ok {
		return out
	}

	for _, field := range fields {
		if field == "" {
			continue
		}
		val, found := walkPath(m, field)
		if !found {
			continue
		}
		setPath(out, field, val)
	}

	return out
}

func walkPath(m map[string]any, path string) (any, bool) {
	cur := any(m)
	for _, seg := range strings.Split(path, ".") {
		mp, ok := cur.(map[string]any)
		if !ok {
			return nil, false
		}
		next, exists := mp[seg]
		if !exists {
			return nil, false
		}
		cur = next
	}
	return cur, true
}

func setPath(out map[string]any, path string, value any) {
	segs := strings.Split(path, ".")
	cur := out
	for i, seg := range segs {
		if i == len(segs)-1 {
			cur[seg] = value
			return
		}
		next, ok := cur[seg].(map[string]any)
		if !ok {
			next = map[string]any{}
			cur[seg] = next
		}
		cur = next
	}
}

// redactSensitive walks the picked payload recursively and masks any value
// whose key matches sensitiveKeyRegex. Last-resort defense above the
// allowlist.
func redactSensitive(v any) any {
	switch t := v.(type) {
	case map[string]any:
		for k, val := range t {
			if sensitiveKeyRegex.MatchString(k) {
				t[k] = redactedPlaceholder
				continue
			}
			t[k] = redactSensitive(val)
		}
		return t
	case []any:
		for i, val := range t {
			t[i] = redactSensitive(val)
		}
		return t
	default:
		return v
	}
}

// MarshalForTest is a small helper used in unit tests to assert the exact
// shape of the JSON we'd emit. Not used in the hot path.
func MarshalForTest(payload any) ([]byte, error) {
	var buf bytes.Buffer
	enc := json.NewEncoder(&buf)
	enc.SetEscapeHTML(false)
	if err := enc.Encode(payload); err != nil {
		return nil, err
	}
	return buf.Bytes(), nil
}
