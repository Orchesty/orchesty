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

	out, ok := renderToolResult(raw)
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

	out, ok := renderToolResult(raw)
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

	out, _ := renderToolResult(raw)

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

	out, ok := renderToolResult(raw)
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

	out, ok := renderToolResult(raw)
	if !ok {
		t.Fatalf("expected renderer to recognise list kind")
	}

	if !strings.Contains(out, "transform in etl — (no message) (failed)") {
		t.Fatalf("expected fallback for missing message and absent http/time, got:\n%s", out)
	}
}

func TestRenderToolResult_RecentErrorsEmpty(t *testing.T) {
	raw := []byte(`{"kind":"list","title":"Recent errors","period":"today","items":[]}`)

	out, ok := renderToolResult(raw)
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

	out, ok := renderToolResult(raw)
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

	out, ok := renderToolResult(raw)
	if !ok {
		t.Fatalf("expected renderer to recognise empty timeseries")
	}

	if !strings.Contains(out, "No processes in this period") {
		t.Fatalf("expected empty-state message, got:\n%s", out)
	}
}

func TestRenderToolResult_UnknownKindFallsBack(t *testing.T) {
	raw := []byte(`{"kind":"runs","items":[]}`)

	out, ok := renderToolResult(raw)
	if ok {
		t.Fatalf("renderer should return ok=false for unknown kinds (got %q)", out)
	}
	if out != "" {
		t.Fatalf("renderer should return empty string for unknown kinds, got %q", out)
	}
}

func TestRenderToolResult_InvalidJsonFallsBack(t *testing.T) {
	_, ok := renderToolResult([]byte(`{not json`))
	if ok {
		t.Fatalf("renderer should refuse invalid JSON")
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
