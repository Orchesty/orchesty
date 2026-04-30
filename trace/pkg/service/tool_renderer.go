package service

import (
	"encoding/json"
	"fmt"
	"math"
	"strings"
)

// renderToolResult turns the compact JSON response of a known MCP tool kind
// (`list`, `timeseries`) into a deterministic, user-facing text block. It is
// the cheap-and-reliable counterpart of the LLM summariser pass: the second
// return value reports whether the renderer recognised the shape. When false,
// the caller should fall back to the LLM summariser so unknown / future tool
// kinds are not silently dropped.
//
// The renderer never invents fields and never reads optional fields without
// nil-checks: if the backend stops emitting `topologyName`, the line still
// renders with `nodeName` only. Numeric formatting is fixed (no locale) so
// snapshot tests stay stable across environments.
func renderToolResult(raw []byte) (string, bool) {
	var payload map[string]interface{}
	if err := json.Unmarshal(raw, &payload); err != nil {
		return "", false
	}

	kind, _ := payload["kind"].(string)
	switch kind {
	case "list":
		return renderListResult(payload), true
	case "timeseries":
		return renderTimeseriesResult(payload), true
	default:
		return "", false
	}
}

// renderListResult formats `kind: list` envelopes. Three item shapes are
// supported today:
//
//   - Failing-connector ranking — items carry `failed` / `success` /
//     `failureRate`. Rendered as "node in topology — N failed, M succeeded".
//   - Recent errors — items carry `resultMessage` / `resultStatus` /
//     `httpStatus` / `finishedAt`. Rendered as "node in topology —
//     "<message>" (failed, HTTP 500, 2026-04-26T21:00…)".
//   - Topologies activity — items carry `runs` plus `success` / `failed` /
//     `running`. Rendered as "<topology> — N runs (S succeeded, F failed,
//     R running), last at <time>".
//
// All shapes share the title/period header and the empty-state message. The
// shape is detected per-item rather than per-payload so a future tool can
// safely mix shapes if needed (e.g. failing connectors with sample messages).
// Detection ordering matters: `resultMessage` (recent errors) takes priority,
// then `runs` (topologies activity) — failing connectors don't carry either,
// so they fall through to the ranked-item branch.
func renderListResult(payload map[string]interface{}) string {
	title := stringOrDefault(payload, "title", "Result")
	period := stringOrDefault(payload, "period", "")
	items, _ := payload["items"].([]interface{})

	var sb strings.Builder
	if period != "" {
		sb.WriteString(fmt.Sprintf("%s (%s):", title, period))
	} else {
		sb.WriteString(fmt.Sprintf("%s:", title))
	}

	if len(items) == 0 {
		sb.WriteString("\nNo entries in this period.")

		return sb.String()
	}

	for _, raw := range items {
		item, ok := raw.(map[string]interface{})
		if !ok {
			continue
		}

		var line string
		switch {
		case hasKey(item, "resultMessage"):
			line = renderErrorItem(item)
		case hasKey(item, "runs"):
			line = renderTopologyActivityItem(item)
		default:
			line = renderRankedItem(item)
		}

		if line == "" {
			continue
		}

		sb.WriteString("\n")
		sb.WriteString(line)
	}

	return sb.String()
}

// hasKey reports whether the JSON object literally carries the given key,
// even when its value is null. Plain `_, ok := m[key]` is intentional — the
// per-item shape detector only cares about presence, not nil-ness.
func hasKey(item map[string]interface{}, key string) bool {
	_, ok := item[key]

	return ok
}

// renderTopologyActivityItem formats one row of the topologies-activity list.
// The topology name is the headline, run count is the primary number; the
// success / failed / running breakdown is appended only for parts that are
// non-zero so single-shot topologies don't get noisy "(0 succeeded, 0 failed,
// 0 running)" tails. The last-run timestamp anchors the row in time when the
// caller asked for a long window.
func renderTopologyActivityItem(item map[string]interface{}) string {
	topologyName := stringOrDefault(item, "topologyName", stringOrDefault(item, "topologyId", "(unnamed topology)"))
	runs := numberAsInt(item["runs"])
	success := numberAsInt(item["success"])
	failed := numberAsInt(item["failed"])
	running := numberAsInt(item["running"])

	var line strings.Builder
	line.WriteString("- ")
	line.WriteString(topologyName)
	line.WriteString(" — ")
	line.WriteString(fmt.Sprintf("%d run%s", runs, plural(runs)))

	parts := make([]string, 0, 3)
	if success > 0 {
		parts = append(parts, fmt.Sprintf("%d succeeded", success))
	}
	if failed > 0 {
		parts = append(parts, fmt.Sprintf("%d failed", failed))
	}
	if running > 0 {
		parts = append(parts, fmt.Sprintf("%d running", running))
	}
	if len(parts) > 0 {
		line.WriteString(" (")
		line.WriteString(strings.Join(parts, ", "))
		line.WriteString(")")
	}

	if lastRun := stringOrDefault(item, "lastRunAt", ""); lastRun != "" {
		line.WriteString(", last at ")
		line.WriteString(lastRun)
	}

	return line.String()
}

func plural(n int) string {
	if n == 1 {
		return ""
	}

	return "s"
}

// renderRankedItem formats one row of the failing-connector list.
func renderRankedItem(item map[string]interface{}) string {
	nodeName := stringOrDefault(item, "nodeName", stringOrDefault(item, "nodeId", "(unnamed node)"))
	topologyName := stringOrDefault(item, "topologyName", stringOrDefault(item, "topologyId", ""))

	failed := numberAsInt(item["failed"])
	success := numberAsInt(item["success"])
	failureRate := numberAsFloat(item["failureRate"])

	var line strings.Builder
	line.WriteString("- ")
	line.WriteString(nodeName)

	if topologyName != "" && topologyName != nodeName {
		line.WriteString(" in ")
		line.WriteString(topologyName)
	}

	line.WriteString(" — ")
	line.WriteString(fmt.Sprintf("%d failed, %d succeeded", failed, success))

	if failureRate > 0 {
		line.WriteString(fmt.Sprintf(" (%s failure rate)", formatPercent(failureRate)))
	}

	return line.String()
}

// renderErrorItem formats one row of the recent-errors list. Optional fields
// (HTTP status, finished timestamp, status name) are appended only when
// present so missing telemetry never produces a "(HTTP 0)" stub.
func renderErrorItem(item map[string]interface{}) string {
	nodeName := stringOrDefault(item, "nodeName", stringOrDefault(item, "nodeId", "(unnamed node)"))
	topologyName := stringOrDefault(item, "topologyName", stringOrDefault(item, "topologyId", ""))
	message := strings.TrimSpace(stringOrDefault(item, "resultMessage", ""))
	status := stringOrDefault(item, "resultStatus", "")
	finished := stringOrDefault(item, "finishedAt", "")

	httpStatus := 0
	if raw, ok := item["httpStatus"]; ok && raw != nil {
		httpStatus = numberAsInt(raw)
	}

	var line strings.Builder
	line.WriteString("- ")
	line.WriteString(nodeName)

	if topologyName != "" && topologyName != nodeName {
		line.WriteString(" in ")
		line.WriteString(topologyName)
	}

	line.WriteString(" — ")
	if message != "" {
		line.WriteString(fmt.Sprintf("%q", message))
	} else {
		line.WriteString("(no message)")
	}

	annotations := make([]string, 0, 3)
	if status != "" {
		annotations = append(annotations, status)
	}
	if httpStatus > 0 {
		annotations = append(annotations, fmt.Sprintf("HTTP %d", httpStatus))
	}
	if finished != "" {
		annotations = append(annotations, finished)
	}

	if len(annotations) > 0 {
		line.WriteString(" (")
		line.WriteString(strings.Join(annotations, ", "))
		line.WriteString(")")
	}

	return line.String()
}

// renderTimeseriesResult formats `kind: timeseries` envelopes (today:
// processes_timeseries). It surfaces totals + a one-line "peak bucket" hint
// so the user sees both the aggregate and the worst slot at a glance.
func renderTimeseriesResult(payload map[string]interface{}) string {
	title := stringOrDefault(payload, "title", "Time series")
	period := stringOrDefault(payload, "period", "")

	total := numberAsInt(payload["total"])
	failed := numberAsInt(payload["failed"])
	success := numberAsInt(payload["success"])

	var sb strings.Builder
	if period != "" {
		sb.WriteString(fmt.Sprintf("%s (%s):", title, period))
	} else {
		sb.WriteString(fmt.Sprintf("%s:", title))
	}

	if total == 0 {
		sb.WriteString("\nNo processes in this period.")

		return sb.String()
	}

	failureRate := 0.0
	if total > 0 {
		failureRate = float64(failed) / float64(total)
	}

	sb.WriteString(fmt.Sprintf(
		"\nTotal %d processes — %d succeeded, %d failed (%s failure rate).",
		total, success, failed, formatPercent(failureRate),
	))

	if peak := findPeakPoint(payload); peak != "" {
		sb.WriteString("\n")
		sb.WriteString(peak)
	}

	return sb.String()
}

// findPeakPoint returns a short "Peak: N (S/F) at TIME" sentence for the
// busiest bucket, or an empty string when no usable points are present.
func findPeakPoint(payload map[string]interface{}) string {
	points, ok := payload["points"].([]interface{})
	if !ok || len(points) == 0 {
		return ""
	}

	var (
		peakTotal  = -1
		peakSucc   = 0
		peakFailed = 0
		peakTime   = ""
	)

	for _, raw := range points {
		point, ok := raw.(map[string]interface{})
		if !ok {
			continue
		}

		s := numberAsInt(point["success"])
		f := numberAsInt(point["failed"])
		total := s + f
		if total <= peakTotal {
			continue
		}

		peakTotal = total
		peakSucc = s
		peakFailed = f
		peakTime = stringOrDefault(point, "time", "")
	}

	if peakTotal <= 0 || peakTime == "" {
		return ""
	}

	return fmt.Sprintf(
		"Peak: %d processes (%d succeeded, %d failed) at %s.",
		peakTotal, peakSucc, peakFailed, peakTime,
	)
}

func stringOrDefault(payload map[string]interface{}, key, fallback string) string {
	if value, ok := payload[key].(string); ok && value != "" {
		return value
	}

	return fallback
}

// numberAsInt converts the loose JSON number into an int. encoding/json decodes
// JSON numbers into float64 by default, so a plain type assertion misses ints
// from Go callers that pre-marshalled int values.
func numberAsInt(raw interface{}) int {
	switch value := raw.(type) {
	case float64:
		return int(value)
	case float32:
		return int(value)
	case int:
		return value
	case int32:
		return int(value)
	case int64:
		return int(value)
	case json.Number:
		if i, err := value.Int64(); err == nil {
			return int(i)
		}
		if f, err := value.Float64(); err == nil {
			return int(f)
		}
	}

	return 0
}

func numberAsFloat(raw interface{}) float64 {
	switch value := raw.(type) {
	case float64:
		return value
	case float32:
		return float64(value)
	case int:
		return float64(value)
	case int64:
		return float64(value)
	case json.Number:
		if f, err := value.Float64(); err == nil {
			return f
		}
	}

	return 0
}

// formatPercent renders 0.1234 as "12.3%". Rounds to one decimal first to
// dodge float artefacts (0.12 * 100 = 11.999...) and drops the decimal when
// the rounded value is a whole number.
func formatPercent(ratio float64) string {
	pct := math.Round(ratio*1000) / 10
	if pct >= 100 {
		return "100%"
	}

	if pct == math.Trunc(pct) {
		return fmt.Sprintf("%d%%", int(pct))
	}

	return fmt.Sprintf("%.1f%%", pct)
}
