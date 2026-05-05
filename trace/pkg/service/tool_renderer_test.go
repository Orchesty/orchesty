package service

import (
	"strings"
	"testing"
)

func TestRenderToolResult_ListWithItems(t *testing.T) {
	raw := []byte(`{
		"kind": "list",
		"title": "Top failing connectors",
		"period": "2026-04-19T00:00:00+00:00..2026-04-26T00:00:00+00:00",
		"items": [
			{"nodeId": "n-1", "nodeName": "form", "topologyId": "t-1", "topologyName": "order-sync", "failed": 6, "success": 44, "failureRate": 0.12},
			{"nodeId": "n-2", "nodeName": "ship", "topologyId": "t-2", "topologyName": "shipping", "failed": 3, "success": 7, "failureRate": 0.3}
		]
	}`)

	out, ok := renderToolResult("", raw)
	if !ok {
		t.Fatalf("expected renderer to recognise list kind")
	}

	for _, want := range []string{
		"Top failing connectors",
		"2026-04-19T00:00:00+00:00..2026-04-26T00:00:00+00:00",
		"form in order-sync — 6 failed, 44 succeeded (12% failure rate)",
		"ship in shipping — 3 failed, 7 succeeded (30% failure rate)",
	} {
		if !strings.Contains(out, want) {
			t.Fatalf("missing %q in output:\n%s", want, out)
		}
	}
}

func TestRenderToolResult_ListEmpty(t *testing.T) {
	raw := []byte(`{"kind": "list", "title": "Top failing connectors", "period": "today", "items": []}`)

	out, ok := renderToolResult("", raw)
	if !ok {
		t.Fatalf("expected renderer to recognise empty list")
	}

	if !strings.Contains(out, "No entries in this period") {
		t.Fatalf("expected empty-state message, got:\n%s", out)
	}
}

func TestRenderToolResult_ListFallsBackToNodeIdWhenNameMissing(t *testing.T) {
	raw := []byte(`{
		"kind": "list",
		"title": "Top failing connectors",
		"items": [{"nodeId": "n-orphan", "topologyId": "t-1", "failed": 2, "success": 0, "failureRate": 1}]
	}`)

	out, _ := renderToolResult("", raw)

	if !strings.Contains(out, "n-orphan") {
		t.Fatalf("expected node id fallback, got:\n%s", out)
	}
	if !strings.Contains(out, "(100% failure rate)") {
		t.Fatalf("expected 100%% rendering, got:\n%s", out)
	}
}

func TestRenderToolResult_RecentErrors(t *testing.T) {
	raw := []byte(`{
		"kind": "list",
		"title": "Recent errors",
		"period": "2026-04-19T00:00:00+00:00..2026-04-26T00:00:00+00:00",
		"items": [
			{
				"correlationId": "c-1",
				"nodeName": "post-order",
				"topologyName": "order-sync",
				"resultStatus": "failed",
				"resultMessage": "Bad request: missing email",
				"httpStatus": 400,
				"finishedAt": "2026-04-26T20:30:00+00:00"
			},
			{
				"correlationId": "c-2",
				"nodeName": "ship",
				"topologyName": "shipping",
				"resultStatus": "limit",
				"resultMessage": "Rate limited by upstream",
				"httpStatus": 429,
				"finishedAt": "2026-04-26T20:25:00+00:00"
			}
		]
	}`)

	out, ok := renderToolResult("", raw)
	if !ok {
		t.Fatalf("expected renderer to recognise list kind")
	}

	for _, want := range []string{
		"Recent errors (",
		`post-order in order-sync — "Bad request: missing email" (failed, HTTP 400, 2026-04-26T20:30:00+00:00)`,
		`ship in shipping — "Rate limited by upstream" (limit, HTTP 429, 2026-04-26T20:25:00+00:00)`,
	} {
		if !strings.Contains(out, want) {
			t.Fatalf("missing %q in output:\n%s", want, out)
		}
	}
}

func TestRenderToolResult_RecentErrorsMissingFields(t *testing.T) {
	raw := []byte(`{
		"kind": "list",
		"title": "Recent errors",
		"items": [
			{"nodeName": "transform", "topologyName": "etl", "resultMessage": "", "resultStatus": "failed"}
		]
	}`)

	out, ok := renderToolResult("", raw)
	if !ok {
		t.Fatalf("expected renderer to recognise list kind")
	}

	if !strings.Contains(out, "transform in etl — (no message) (failed)") {
		t.Fatalf("expected fallback for missing message and absent http/time, got:\n%s", out)
	}
}

func TestRenderToolResult_RecentErrorsEmpty(t *testing.T) {
	raw := []byte(`{"kind":"list","title":"Recent errors","period":"today","items":[]}`)

	out, ok := renderToolResult("", raw)
	if !ok {
		t.Fatalf("expected renderer to recognise list kind")
	}

	if !strings.Contains(out, "Recent errors (today):") {
		t.Fatalf("missing title in output:\n%s", out)
	}

	if !strings.Contains(out, "No entries in this period") {
		t.Fatalf("expected empty-state message, got:\n%s", out)
	}
}

func TestRenderToolResult_TopologiesActivity(t *testing.T) {
	raw := []byte(`{
		"kind": "list",
		"title": "Topologies active in range",
		"period": "today",
		"items": [
			{"topologyId": "t-1", "topologyName": "order-sync", "runs": 12, "success": 10, "failed": 2, "running": 0, "lastRunAt": "2026-04-30T01:14:00+00:00", "firstRunAt": "2026-04-30T00:02:00+00:00"},
			{"topologyId": "t-2", "topologyName": "shipping", "runs": 1, "success": 1, "failed": 0, "running": 0, "lastRunAt": "2026-04-30T00:42:00+00:00"},
			{"topologyId": "t-3", "topologyName": "etl", "runs": 3, "success": 0, "failed": 0, "running": 3, "lastRunAt": "2026-04-30T01:30:00+00:00"}
		]
	}`)

	out, ok := renderToolResult("", raw)
	if !ok {
		t.Fatalf("expected renderer to recognise list kind")
	}

	for _, want := range []string{
		"Topologies active in range (today):",
		"order-sync — 12 runs (10 succeeded, 2 failed), last at 2026-04-30T01:14:00+00:00",
		"shipping — 1 run (1 succeeded), last at 2026-04-30T00:42:00+00:00",
		"etl — 3 runs (3 running), last at 2026-04-30T01:30:00+00:00",
	} {
		if !strings.Contains(out, want) {
			t.Fatalf("missing %q in output:\n%s", want, out)
		}
	}
}

func TestRenderToolResult_TopologiesActivityFallsBackToTopologyId(t *testing.T) {
	raw := []byte(`{
		"kind": "list",
		"title": "Topologies active in range",
		"items": [{"topologyId": "t-orphan", "runs": 0, "success": 0, "failed": 0, "running": 0}]
	}`)

	out, _ := renderToolResult("", raw)

	// `topologyName` missing → renderer should fall back to topologyId, and a
	// zero-run row should still print without an empty parenthesis tail.
	if !strings.Contains(out, "t-orphan — 0 runs") {
		t.Fatalf("expected topology id fallback with 0 runs, got:\n%s", out)
	}
	if strings.Contains(out, "()") {
		t.Fatalf("renderer should suppress empty parens when no breakdown, got:\n%s", out)
	}
}

func TestRenderToolResult_TimeseriesWithPoints(t *testing.T) {
	raw := []byte(`{
		"kind": "timeseries",
		"title": "Processes (all topologies)",
		"period": "last_7d",
		"total": 124,
		"failed": 24,
		"success": 100,
		"points": [
			{"time": "2026-04-20T00:00:00Z", "success": 10, "failed": 2},
			{"time": "2026-04-22T00:00:00Z", "success": 24, "failed": 6},
			{"time": "2026-04-24T00:00:00Z", "success": 30, "failed": 4}
		]
	}`)

	out, ok := renderToolResult("", raw)
	if !ok {
		t.Fatalf("expected renderer to recognise timeseries kind")
	}

	for _, want := range []string{
		"Processes (all topologies) (last_7d):",
		"Total 124 processes — 100 succeeded, 24 failed",
		"19.4% failure rate",
		"Peak: 34 processes (30 succeeded, 4 failed) at 2026-04-24T00:00:00Z",
	} {
		if !strings.Contains(out, want) {
			t.Fatalf("missing %q in output:\n%s", want, out)
		}
	}
}

func TestRenderToolResult_TimeseriesEmpty(t *testing.T) {
	raw := []byte(`{"kind":"timeseries","title":"Processes (all topologies)","period":"today","total":0,"failed":0,"success":0,"points":[]}`)

	out, ok := renderToolResult("", raw)
	if !ok {
		t.Fatalf("expected renderer to recognise empty timeseries")
	}

	if !strings.Contains(out, "No processes in this period") {
		t.Fatalf("expected empty-state message, got:\n%s", out)
	}
}

func TestRenderToolResult_UnknownKindFallsBack(t *testing.T) {
	raw := []byte(`{"kind":"runs","items":[]}`)

	out, ok := renderToolResult("", raw)
	if ok {
		t.Fatalf("renderer should return ok=false for unknown kinds (got %q)", out)
	}
	if out != "" {
		t.Fatalf("renderer should return empty string for unknown kinds, got %q", out)
	}
}

func TestRenderToolResult_InvalidJsonFallsBack(t *testing.T) {
	_, ok := renderToolResult("", []byte(`{not json`))
	if ok {
		t.Fatalf("renderer should refuse invalid JSON")
	}
}

// TestRenderOnboardingStep_FullShape is the canonical happy-path snapshot:
// a stage with all three action kinds (shell + prompt + link), an intro
// with multiple paragraphs and inline code, and a `next` follow-up. The
// expected output is byte-for-byte the format the FE parser
// (traceMessageParser.ts) understands. Any drift here breaks the
// onboarding card render in the chat drawer.
func TestRenderOnboardingStep_FullShape(t *testing.T) {
	raw := []byte(`{
		"stage": "clone-starter-ai",
		"title": "Scaffold the worker project",
		"description": "Bootstrap a worker repo via AI or manual.",
		"intro": "Two ways to scaffold the worker. Pick the one that matches how you work today.\n\n- AI editor — paste the bootstrap prompt below and let the agent set everything up.\n- Manual — run the shell snippet, configure ` + "`.env`" + `, verify the build.",
		"prerequisites": ["choose-your-way"],
		"next": "build-components-ai",
		"actions": [
			{"kind": "prompt", "label": "AI Bootstrap prompt", "value": "Clone the starter and read AI-INSTRUCTIONS.md."},
			{"kind": "shell", "label": "Manual scaffold", "value": "git clone https://github.com/Orchesty/worker-ai-starter.git my-orchesty-worker\ncd my-orchesty-worker\nnpm install"},
			{"kind": "link", "label": "Build your first worker", "href": "https://orchesty.io/learn/get-started/build-your-first-worker"}
		],
		"path": "/onboarding/clone-starter-ai",
		"stages": ["overview", "choose-your-way", "clone-starter-ai", "build-components-ai"]
	}`)

	out, ok := renderToolResult("onboarding_step", raw)
	if !ok {
		t.Fatalf("expected onboarding renderer to recognise the payload")
	}

	want := "[onboarding-stage:clone-starter-ai next=build-components-ai]\n" +
		"\n" +
		"# Scaffold the worker project\n" +
		"\n" +
		"Two ways to scaffold the worker. Pick the one that matches how you work today.\n" +
		"\n" +
		"- AI editor — paste the bootstrap prompt below and let the agent set everything up.\n" +
		"- Manual — run the shell snippet, configure `.env`, verify the build.\n" +
		"\n" +
		"[prompt] AI Bootstrap prompt\n" +
		"````\n" +
		"Clone the starter and read AI-INSTRUCTIONS.md.\n" +
		"````\n" +
		"\n" +
		"[shell] Manual scaffold\n" +
		"```bash\n" +
		"git clone https://github.com/Orchesty/worker-ai-starter.git my-orchesty-worker\n" +
		"cd my-orchesty-worker\n" +
		"npm install\n" +
		"```\n" +
		"\n" +
		"[link] Build your first worker\n" +
		"https://orchesty.io/learn/get-started/build-your-first-worker\n" +
		"\n" +
		"Reply `next` when you're ready to continue."

	if out != want {
		t.Fatalf("renderer drift; got:\n---\n%s\n---\nwant:\n---\n%s\n---", out, want)
	}
}

// TestRenderOnboardingStep_TerminalStageNoCTA pins the terminal-stage
// behaviour: when `next` is null/empty, the closing CTA "Reply `next`…"
// must NOT appear. Terminal stages (verify, add-a-node hub, per-node-type
// leaves) intentionally hand back to the user — printing a CTA there
// would lie about what comes after.
func TestRenderOnboardingStep_TerminalStageNoCTA(t *testing.T) {
	raw := []byte(`{
		"stage": "verify",
		"title": "Verify the run",
		"intro": "You should see a green process card.",
		"next": null,
		"actions": []
	}`)

	out, ok := renderToolResult("onboarding_step", raw)
	if !ok {
		t.Fatalf("expected renderer to handle terminal stage")
	}

	if !strings.HasPrefix(out, "[onboarding-stage:verify]\n") {
		t.Fatalf("expected marker without next=, got:\n%s", out)
	}
	if strings.Contains(out, "next=") {
		t.Fatalf("terminal stage marker must not contain next=, got:\n%s", out)
	}
	if strings.Contains(out, "Reply `next`") {
		t.Fatalf("terminal stage must not emit the closing CTA, got:\n%s", out)
	}
}

// TestRenderOnboardingStep_NextEmptyStringTreatedAsTerminal guards against
// the edge case where the Nuxt endpoint emits `"next": ""` rather than
// `null` (it shouldn't, but defensive parsing is cheaper than a
// post-incident debug). Empty next == terminal stage == no CTA, no
// `next=` segment in the marker.
func TestRenderOnboardingStep_NextEmptyStringTreatedAsTerminal(t *testing.T) {
	raw := []byte(`{
		"stage": "verify",
		"title": "Verify the run",
		"intro": "Inspect the dashboard.",
		"next": "",
		"actions": []
	}`)

	out, _ := renderToolResult("onboarding_step", raw)

	if strings.Contains(out, "next=") {
		t.Fatalf("empty next must not produce a next= segment, got:\n%s", out)
	}
	if strings.Contains(out, "Reply `next`") {
		t.Fatalf("empty next must not produce a closing CTA, got:\n%s", out)
	}
}

// TestRenderOnboardingStep_DegradedPayloadFallsBack returns ok=false for
// the OnboardingStepClient error envelope (`{error, stages: [], actions:
// []}`). The caller falls back to the LLM summariser, which writes a
// graceful "couldn't fetch the step" sentence — better than us inventing
// one in Go. The path is rare (Nuxt content endpoint unreachable) so the
// LLM cost is acceptable.
func TestRenderOnboardingStep_DegradedPayloadFallsBack(t *testing.T) {
	raw := []byte(`{"error": "onboarding step request failed: connection refused", "stages": [], "actions": []}`)

	if _, ok := renderToolResult("onboarding_step", raw); ok {
		t.Fatalf("renderer must defer to the LLM summariser on error envelopes")
	}
}

// TestRenderOnboardingStep_MissingStageOrTitleFallsBack pins the second
// fallback case: the FE parser needs `stage` (for the marker) and `title`
// (for the heading). Anything else is ok to be empty, but without those
// two we don't have a renderable card — defer to the summariser.
func TestRenderOnboardingStep_MissingStageOrTitleFallsBack(t *testing.T) {
	cases := map[string][]byte{
		"missing stage": []byte(`{"title": "x", "intro": "y", "actions": []}`),
		"missing title": []byte(`{"stage": "x", "intro": "y", "actions": []}`),
		"both empty":    []byte(`{"stage": "", "title": "", "intro": "y", "actions": []}`),
	}

	for label, raw := range cases {
		if _, ok := renderToolResult("onboarding_step", raw); ok {
			t.Fatalf("[%s] renderer must fall back when stage or title is missing", label)
		}
	}
}

// TestRenderOnboardingStep_MalformedActionsAreSkipped ensures the renderer
// is tolerant of partial actions that slipped past Nuxt's
// `normaliseAction` (defensive belt-and-braces — both layers should drop
// them). Skipping silently keeps the rest of the card intact rather than
// emitting a half-broken `[shell]` card with no body.
func TestRenderOnboardingStep_MalformedActionsAreSkipped(t *testing.T) {
	raw := []byte(`{
		"stage": "demo",
		"title": "Demo",
		"intro": "Body.",
		"next": "next-stage",
		"actions": [
			{"kind": "shell", "label": "", "value": "echo hi"},
			{"kind": "shell", "label": "Empty value", "value": ""},
			{"kind": "link", "label": "No href", "href": ""},
			{"kind": "unknown", "label": "Wrong kind", "value": "noop"},
			"not an object",
			{"kind": "shell", "label": "Good one", "value": "echo ok"}
		]
	}`)

	out, ok := renderToolResult("onboarding_step", raw)
	if !ok {
		t.Fatalf("expected renderer to handle the payload despite malformed entries")
	}

	if !strings.Contains(out, "[shell] Good one") {
		t.Fatalf("the one well-formed action must still render, got:\n%s", out)
	}
	for _, banned := range []string{"Empty value", "No href", "Wrong kind"} {
		if strings.Contains(out, banned) {
			t.Fatalf("malformed action %q must be skipped, got:\n%s", banned, out)
		}
	}
}

// TestRenderOnboardingStep_PromptWithNestedCodeFence pins the nested-fence
// behaviour for prompt actions: the AI Bootstrap prompt embeds
// ```bash ... ``` snippets inside its body. With a 3-backtick outer fence
// the inner closer would terminate the outer card prematurely, leaking
// "Step 2 ..." onwards as plain markdown outside the [prompt] block. The
// renderer therefore wraps prompt values in a 4-backtick fence so the
// inner 3-backtick block stays nested. CommonMark rule: closing fence
// must match the opening length, and 3 < 4.
func TestRenderOnboardingStep_PromptWithNestedCodeFence(t *testing.T) {
	raw := []byte(`{
		"stage": "clone-starter-ai",
		"title": "Scaffold the worker (AI path)",
		"intro": "Paste the prompt below into your AI editor.",
		"next": "build-components-ai",
		"actions": [
			{"kind": "prompt", "label": "AI Bootstrap prompt", "value": "# Orchesty Worker\n\n## Step 1: Clone\n\n` + "```bash\\n" + `git clone https://github.com/orchesty/worker-ai-starter.git .\n` + "```\\n\\n" + `## Step 2: Setup\n\nFollow AI-INSTRUCTIONS.md."}
		]
	}`)

	out, ok := renderToolResult("onboarding_step", raw)
	if !ok {
		t.Fatalf("expected renderer to accept the payload")
	}

	if !strings.Contains(out, "[prompt] AI Bootstrap prompt\n````\n") {
		t.Fatalf("prompt action must be wrapped in a 4-backtick fence so nested ```lang ... ``` blocks don't break out, got:\n%s", out)
	}
	if !strings.Contains(out, "\n````\n\nReply `next`") {
		t.Fatalf("prompt 4-backtick fence must close with a matching 4-backtick line right before the closing CTA, got:\n%s", out)
	}
	// The inner ```bash and its closing ``` must survive verbatim inside the
	// prompt body — that's the whole point of bumping the outer fence.
	if !strings.Contains(out, "```bash\ngit clone https://github.com/orchesty/worker-ai-starter.git .\n```") {
		t.Fatalf("inner ```bash code block inside the prompt body must be preserved verbatim, got:\n%s", out)
	}
	// And the Step 2 markdown that lives AFTER the inner ``` must still be
	// inside the prompt body, not leaked to the outer card. We assert this
	// indirectly: between the 4-backtick fences there must be exactly one
	// substring containing "Step 2", and it must come before the closing
	// 4-backtick fence.
	idxStep2 := strings.Index(out, "## Step 2: Setup")
	idxCloseFence := strings.Index(out, "\n````\n\nReply `next`")
	if idxStep2 == -1 || idxStep2 >= idxCloseFence {
		t.Fatalf("Step 2 content must remain inside the 4-backtick prompt fence, got:\n%s", out)
	}
}

// TestRenderOnboardingStep_NoIntroOrActionsStillRenders covers the
// minimal-marker case (a stub stage with a title only). The card should
// still be valid and parseable — just shorter.
func TestRenderOnboardingStep_NoIntroOrActionsStillRenders(t *testing.T) {
	raw := []byte(`{
		"stage": "stub",
		"title": "Stub stage",
		"next": "next-stage",
		"actions": []
	}`)

	out, ok := renderToolResult("onboarding_step", raw)
	if !ok {
		t.Fatalf("expected renderer to accept a minimal payload")
	}

	want := "[onboarding-stage:stub next=next-stage]\n" +
		"\n" +
		"# Stub stage\n" +
		"\n" +
		"Reply `next` when you're ready to continue."

	if out != want {
		t.Fatalf("minimal-payload drift; got:\n---\n%s\n---\nwant:\n---\n%s\n---", out, want)
	}
}

// TestRenderOnboardingStep_RoutesByToolIDNotPayloadKind locks in the
// tool-routing strategy: onboarding payloads carry no `kind` field, so
// the dispatcher matches on `toolID` instead. Calling the renderer with
// `toolID="onboarding_step"` and a payload missing `kind` MUST still
// route through the onboarding path; a rogue `kind: "list"` value MUST
// NOT divert it to the list renderer.
func TestRenderOnboardingStep_RoutesByToolIDNotPayloadKind(t *testing.T) {
	raw := []byte(`{
		"kind": "list",
		"stage": "demo",
		"title": "Demo",
		"intro": "Body.",
		"actions": []
	}`)

	out, ok := renderToolResult("onboarding_step", raw)
	if !ok {
		t.Fatalf("toolID-based routing must override payload kind")
	}
	if !strings.Contains(out, "[onboarding-stage:demo]") {
		t.Fatalf("expected onboarding marker, got:\n%s", out)
	}
}

func TestFormatPercent(t *testing.T) {
	cases := map[float64]string{
		0:     "0%",
		0.12:  "12%",
		0.123: "12.3%",
		0.999: "99.9%",
		1:     "100%",
		1.5:   "100%",
	}

	for ratio, want := range cases {
		if got := formatPercent(ratio); got != want {
			t.Fatalf("formatPercent(%v) = %q, want %q", ratio, got, want)
		}
	}
}
