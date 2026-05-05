package service

import (
	"encoding/json"
	"fmt"
	"math"
	"strings"
)

// renderToolResult turns the compact JSON response of a known MCP tool kind
// (`list`, `timeseries`, `onboarding_step`) into a deterministic, user-facing
// text block. It is the cheap-and-reliable counterpart of the LLM summariser
// pass: the second return value reports whether the renderer recognised the
// shape. When false, the caller should fall back to the LLM summariser so
// unknown / future tool kinds are not silently dropped.
//
// Tool routing strategy:
//   - `onboarding_step` is matched by `toolID` because the payload itself
//     carries no `kind` field (its shape is `{stage, title, intro, actions[],
//     next}`). Routing by toolID also lets us short-circuit the JSON kind
//     switch entirely, so onboarding never accidentally falls through to a
//     timeseries renderer if a future field collision happens.
//   - List / timeseries tools are matched by `payload["kind"]` because a
//     single tool can return multiple shapes (e.g. metrics returns either,
//     depending on aggregation).
//
// The renderer never invents fields and never reads optional fields without
// nil-checks: if the backend stops emitting `topologyName`, the line still
// renders with `nodeName` only. Numeric formatting is fixed (no locale) so
// snapshot tests stay stable across environments.
func renderToolResult(toolID string, raw []byte) (string, bool) {
	if toolID == "onboarding_step" {
		return renderOnboardingStep(raw)
	}

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

// renderOnboardingStep is the deterministic Go renderer that replaces the
// expensive LLM summariser pass for the onboarding wizard. It produces the
// EXACT plain-text shape the FE parser (`traceMessageParser.ts`) expects:
//
//   [onboarding-stage:<stage> next=<next>]
//
//   # <title>
//
//   <intro verbatim>
//
//   [shell] <label>
//   ```bash
//   <value verbatim>
//   ```
//
//   [prompt] <label>
//   ```
//   <value verbatim>
//   ```
//
//   [link] <label>
//   <href>
//
//   Reply `next` when you're ready to continue.
//
// Why a Go renderer and not the LLM summariser:
//   - The LLM was burning 4–7 s per onboarding turn doing what amounts to a
//     deterministic XSL transform — every "creative" liberty it took was a
//     regression (paraphrased intros, dropped action cards, hallucinated
//     shell snippets, "Before you continue" sections that don't belong).
//   - The summariser prompt is now ~5 KB of "DO NOT do X" rules. We keep the
//     prompt as a fallback (`buildOnboardingSummariserPrompt`) for partial /
//     malformed payloads, but the happy path no longer spends a token.
//
// Fallback semantics: returns ("", false) for degraded payloads (controller
// `error` envelope, missing `stage` or `title`). The caller then falls back
// to the LLM summariser, which is better at writing apologetic prose for
// "couldn't fetch the step" cases than a deterministic renderer would be.
//
// The output is RIGHT-trimmed of trailing newlines so the WebSocket frame
// the FE receives ends cleanly without an extra blank line below the CTA.
func renderOnboardingStep(raw []byte) (string, bool) {
	var payload map[string]interface{}
	if err := json.Unmarshal(raw, &payload); err != nil {
		return "", false
	}

	// Degraded payload from OnboardingStepClient — let the LLM summariser
	// craft a graceful "couldn't fetch the step" message instead of us
	// inventing one in Go. This path is rare (network / auth failures
	// reaching Nuxt content endpoint), so the LLM cost is acceptable.
	if errMsg, _ := payload["error"].(string); strings.TrimSpace(errMsg) != "" {
		return "", false
	}

	stage := strings.TrimSpace(getString(payload, "stage"))
	title := strings.TrimSpace(getString(payload, "title"))
	if stage == "" || title == "" {
		// Missing the bare minimum the FE parser needs to recognise the
		// stage marker. Defer to the summariser rather than emit a
		// half-broken card.
		return "", false
	}

	intro := getString(payload, "intro")
	next := strings.TrimSpace(getString(payload, "next"))

	var sb strings.Builder

	// 1. Hidden stage marker — first line, ALWAYS. `next=` belongs INSIDE
	//    the closing `]` (the parser is tolerant but the canonical form is
	//    what the prompt rules pin and what the regex prefers).
	if next != "" {
		fmt.Fprintf(&sb, "[onboarding-stage:%s next=%s]", stage, next)
	} else {
		fmt.Fprintf(&sb, "[onboarding-stage:%s]", stage)
	}
	sb.WriteString("\n\n")

	// 2. Title heading.
	sb.WriteString("# ")
	sb.WriteString(title)
	sb.WriteString("\n")

	// 3. Intro VERBATIM (paragraph breaks, lists, links, code spans). One
	//    blank line between the title and the intro; force exactly one
	//    trailing newline regardless of whether the payload supplied one.
	trimmedIntro := strings.TrimRight(intro, "\n")
	if trimmedIntro != "" {
		sb.WriteString("\n")
		sb.WriteString(trimmedIntro)
		sb.WriteString("\n")
	}

	// 4. Action blocks. Each block is preceded by exactly one blank line
	//    (so consecutive blocks have a single blank between them, and the
	//    first block has a blank between it and the intro / title).
	rawActions, _ := payload["actions"].([]interface{})
	for _, item := range rawActions {
		block, ok := renderOnboardingAction(item)
		if !ok {
			continue
		}
		sb.WriteString("\n")
		sb.WriteString(block)
		sb.WriteString("\n")
	}

	// 5. Closing CTA — ONLY when the stage has a follow-up. Terminal stages
	//    (verify, add-a-node hub, per-node-type leaves) have `next=null`
	//    and intentionally leave the user to pose the next question.
	if next != "" {
		sb.WriteString("\nReply `next` when you're ready to continue.\n")
	}

	// FE parses by splitting on `\n` — trailing newlines aren't harmful but
	// they leave a blank line in the rendered card. Trim them off.
	return strings.TrimRight(sb.String(), "\n"), true
}

// renderOnboardingAction renders a single action object from `actions[]`
// into one of the three tagged blocks the FE recognises:
//   - shell: `[shell] <label>\n```bash\n<value>\n````
//   - prompt: `[prompt] <label>\n````\n<value>\n````` (4-backtick fence so
//     nested ```lang ... ``` snippets inside the prompt body don't break out)
//   - link: `[link] <label>\n<href>`
//
// Skips silently (returns false) on malformed or partial actions — missing
// kind, empty label, missing value/href — so the caller iterates over the
// rest of `actions[]` without producing half-broken cards. The Nuxt
// endpoint already filters these in `normaliseAction`, so we should rarely
// reach the false branch in practice.
func renderOnboardingAction(raw interface{}) (string, bool) {
	obj, ok := raw.(map[string]interface{})
	if !ok {
		return "", false
	}

	kind := strings.TrimSpace(getString(obj, "kind"))
	label := strings.TrimSpace(getString(obj, "label"))
	if kind == "" || label == "" {
		return "", false
	}

	switch kind {
	case "shell":
		value := strings.TrimRight(getString(obj, "value"), "\n")
		if strings.TrimSpace(value) == "" {
			return "", false
		}
		return fmt.Sprintf("[shell] %s\n```bash\n%s\n```", label, value), true
	case "prompt":
		value := strings.TrimRight(getString(obj, "value"), "\n")
		if strings.TrimSpace(value) == "" {
			return "", false
		}
		// Use 4-backtick fence so prompt values that themselves contain
		// triple-backtick code blocks (very common — e.g. AI Bootstrap
		// prompts that embed ```bash ... ``` snippets) don't break the
		// outer fence. CommonMark requires the closing fence to be at
		// least as long as the opening fence, so a 3-backtick block
		// inside a 4-backtick block stays nested. The FE parser
		// (traceMessageParser.ts) accepts fences of length 3 or more.
		return fmt.Sprintf("[prompt] %s\n````\n%s\n````", label, value), true
	case "link":
		href := strings.TrimSpace(getString(obj, "href"))
		if href == "" {
			return "", false
		}
		return fmt.Sprintf("[link] %s\n%s", label, href), true
	default:
		return "", false
	}
}

// getString reads a string field from a generic JSON map without panicking
// on type mismatches. Returns the empty string when the key is absent or
// the value is not a string. Used by the onboarding renderer where every
// field is optional from Go's POV (the validation lives in the Nuxt
// endpoint) and we never want to crash the WS handler on a malformed
// payload.
func getString(obj map[string]interface{}, key string) string {
	if v, ok := obj[key].(string); ok {
		return v
	}
	return ""
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
