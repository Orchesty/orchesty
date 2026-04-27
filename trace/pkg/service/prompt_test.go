package service

import (
	"strings"
	"testing"
)

func TestBuildSystemPrompt_ContainsAllThreeEnvelopes(t *testing.T) {
	prompt := BuildSystemPrompt(nil)

	for _, marker := range []string{
		`{"audit":"<entity-id>","data":`,
		`{"tool":"<tool-id>","args":`,
		`{"reply":"<short text for the user>"}`,
	} {
		if !strings.Contains(prompt, marker) {
			t.Fatalf("expected prompt to mention envelope marker %q, got:\n%s", marker, prompt)
		}
	}
}

func TestBuildSystemPrompt_MentionsToolExamples(t *testing.T) {
	prompt := BuildSystemPrompt(nil)

	for _, marker := range []string{
		`"tool":"processes_timeseries"`,
		`"tool":"failing_connectors"`,
		`"tool":"recent_errors"`,
	} {
		if !strings.Contains(prompt, marker) {
			t.Fatalf("expected prompt to include example %q, got:\n%s", marker, prompt)
		}
	}
}

func TestBuildSystemPrompt_MentionsAllPeriods(t *testing.T) {
	prompt := BuildSystemPrompt(nil)

	for _, period := range []string{"today", "yesterday", "this_week", "last_7d", "last_30d"} {
		if !strings.Contains(prompt, period) {
			t.Fatalf("expected prompt to mention period %q, got:\n%s", period, prompt)
		}
	}
}

func TestBuildSystemPrompt_GroupsActionsByKind(t *testing.T) {
	actions := []ManifestAction{
		{ID: "product", Title: "Product history", Kind: "entity_history"},
		{ID: "order", Title: "Order history", Kind: "query"},
		{ID: "processes_timeseries", Title: "Process counts", Kind: "timeseries"},
		{ID: "failing_connectors", Title: "Top failing connectors", Kind: "list"},
	}

	prompt := BuildSystemPrompt(actions)

	if !strings.Contains(prompt, "AVAILABLE ENTITIES") {
		t.Fatalf("expected entity section header, got:\n%s", prompt)
	}
	if !strings.Contains(prompt, "AVAILABLE TOOLS") {
		t.Fatalf("expected tools section header, got:\n%s", prompt)
	}

	for _, id := range []string{"product", "order", "processes_timeseries", "failing_connectors"} {
		if !strings.Contains(prompt, `"`+id+`"`) {
			t.Fatalf("expected manifest id %q to appear in prompt, got:\n%s", id, prompt)
		}
	}

	entityIdx := strings.Index(prompt, "AVAILABLE ENTITIES")
	toolsIdx := strings.Index(prompt, "AVAILABLE TOOLS")
	if entityIdx < 0 || toolsIdx < 0 || entityIdx >= toolsIdx {
		t.Fatalf("expected ENTITIES section to come before TOOLS section, got:\n%s", prompt)
	}

	// Entity ids must land in the entity section, tool ids in the tools section.
	productIdx := strings.Index(prompt, `"product"`)
	tsIdx := strings.Index(prompt, `"processes_timeseries"`)
	if productIdx < entityIdx || productIdx > toolsIdx {
		t.Fatalf("entity action 'product' should be listed under AVAILABLE ENTITIES")
	}
	if tsIdx < toolsIdx {
		t.Fatalf("tool action 'processes_timeseries' should be listed under AVAILABLE TOOLS")
	}
}

func TestBuildSystemPrompt_NoActionsFallback(t *testing.T) {
	prompt := BuildSystemPrompt(nil)

	if !strings.Contains(prompt, "(none configured yet)") {
		t.Fatalf("expected fallback for empty entity catalogue, got:\n%s", prompt)
	}
}

func TestBuildSummariserPrompt_HasCoreRules(t *testing.T) {
	prompt := BuildSummariserPrompt("processes_timeseries")

	if !strings.Contains(prompt, `"processes_timeseries"`) {
		t.Fatalf("expected summariser prompt to mention the tool id, got:\n%s", prompt)
	}

	for _, marker := range []string{
		"plain text",
		"NO markdown",
		"NO JSON",
		"empty",
	} {
		if !strings.Contains(prompt, marker) {
			t.Fatalf("expected summariser prompt to contain rule %q, got:\n%s", marker, prompt)
		}
	}
}

func TestBuildSummariserPrompt_UnknownTool(t *testing.T) {
	prompt := BuildSummariserPrompt("")

	if !strings.Contains(prompt, "tool id unknown") {
		t.Fatalf("expected fallback for missing tool id, got:\n%s", prompt)
	}
}

func TestSplitActionsByKind(t *testing.T) {
	entities, tools := splitActionsByKind([]ManifestAction{
		{ID: "a", Kind: ""},
		{ID: "b", Kind: "query"},
		{ID: "c", Kind: "entity_history"},
		{ID: "d", Kind: "timeseries"},
		{ID: "e", Kind: "list"},
	})

	if len(entities) != 3 || entities[0].ID != "a" || entities[1].ID != "b" || entities[2].ID != "c" {
		t.Fatalf("entity bucket mismatch: %+v", entities)
	}
	if len(tools) != 2 || tools[0].ID != "d" || tools[1].ID != "e" {
		t.Fatalf("tool bucket mismatch: %+v", tools)
	}
}
