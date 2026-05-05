package service

import (
	"strings"
	"testing"
)

// TestParseOnboardingStageMarker_Shapes pins the recognised stage marker
// shapes the deterministic renderer emits — and the negative cases the
// post-LLM guard MUST not misclassify (quoted markers in prose, partial
// brackets, wrong prefixes). Without this matrix the regex could drift and
// silently route normal docs replies through the onboarding-step dispatch
// path.
func TestParseOnboardingStageMarker_Shapes(t *testing.T) {
	cases := []struct {
		name      string
		content   string
		wantStage string
		wantNext  string
		wantOK    bool
	}{
		{
			name:      "stage with next",
			content:   "[onboarding-stage:overview next=choose-your-way]\n\n# Welcome\n",
			wantStage: "overview",
			wantNext:  "choose-your-way",
			wantOK:    true,
		},
		{
			name:      "terminal stage without next",
			content:   "[onboarding-stage:choose-your-way]\n\n# Choose your way",
			wantStage: "choose-your-way",
			wantNext:  "",
			wantOK:    true,
		},
		{
			name:      "stage with hyphenated id",
			content:   "[onboarding-stage:add-a-node next=connector-node]\n\nbody",
			wantStage: "add-a-node",
			wantNext:  "connector-node",
			wantOK:    true,
		},
		{
			name:    "leading whitespace before marker is trimmed",
			content: "  \n[onboarding-stage:overview next=choose-your-way]\n",
			wantStage: "overview",
			wantNext:  "choose-your-way",
			wantOK:    true,
		},
		{
			name:    "marker on second line is NOT a stage marker",
			content: "Sure, here's the next step:\n[onboarding-stage:overview]",
			wantOK:  false,
		},
		{
			name:    "quoted marker inside prose is NOT a stage marker",
			content: "Trace replied with `[onboarding-stage:overview]`.",
			wantOK:  false,
		},
		{
			name:    "wrong prefix is NOT a stage marker",
			content: "[stage:overview]\n",
			wantOK:  false,
		},
		{
			name:    "uppercase stage id rejected (renderer always lowercases)",
			content: "[onboarding-stage:Overview]\n",
			wantOK:  false,
		},
		{
			name:    "empty content",
			content: "",
			wantOK:  false,
		},
	}

	for _, tc := range cases {
		t.Run(tc.name, func(t *testing.T) {
			stage, next, ok := parseOnboardingStageMarker(tc.content)
			if ok != tc.wantOK {
				t.Fatalf("ok mismatch: got %v want %v (stage=%q next=%q)", ok, tc.wantOK, stage, next)
			}
			if !ok {
				return
			}
			if stage != tc.wantStage {
				t.Fatalf("stage = %q, want %q", stage, tc.wantStage)
			}
			if next != tc.wantNext {
				t.Fatalf("next = %q, want %q", next, tc.wantNext)
			}
		})
	}
}

// TestNormaliseTriggerInput_Canonicalisation pins the input shapes that
// matchOnboardingTriggerStage treats as equivalent to a canonical trigger
// key. Trailing punctuation, surrounding whitespace and case differences
// must all collapse so the bare-trigger intercept fires consistently.
func TestNormaliseTriggerInput_Canonicalisation(t *testing.T) {
	cases := []struct {
		input string
		want  string
	}{
		{"AI", "ai"},
		{"  ai  ", "ai"},
		{"AI!", "ai"},
		{"manual.", "manual"},
		{"Continue?", "continue"},
		{"  CONTINUE  ", "continue"},
		{"next,", "next"},
		{"Manual please.", "manual please"},
		{"", ""},
	}

	for _, tc := range cases {
		got := normaliseTriggerInput(tc.input)
		if got != tc.want {
			t.Fatalf("normaliseTriggerInput(%q) = %q, want %q", tc.input, got, tc.want)
		}
	}
}

// TestMatchOnboardingTriggerStage_BranchPick verifies that the bare AI /
// manual triggers always restart at the corresponding clone-starter-* stage
// regardless of where the user currently is. This is Layer 1's branch-pick
// behaviour for the intercept — the symmetric LLM-side rule lives in
// prompt.go's "Branch-pick / Branch-switch intent" block.
func TestMatchOnboardingTriggerStage_BranchPick(t *testing.T) {
	cases := []struct {
		input string
		stage string
	}{
		{"AI", "clone-starter-ai"},
		{"ai", "clone-starter-ai"},
		{"AI please", "clone-starter-ai"},
		{"switch to AI", "clone-starter-ai"},
		{"the AI way", "clone-starter-ai"},
		{"manual", "clone-starter-manual"},
		{"Manual.", "clone-starter-manual"},
		{"by hand", "clone-starter-manual"},
		{"switch to manual", "clone-starter-manual"},
		{"the manual way", "clone-starter-manual"},
	}

	for _, tc := range cases {
		t.Run(tc.input, func(t *testing.T) {
			stage, ok := matchOnboardingTriggerStage(tc.input, nil, nil)
			if !ok {
				t.Fatalf("expected match for %q, got none", tc.input)
			}
			if stage != tc.stage {
				t.Fatalf("input %q → stage %q, want %q", tc.input, stage, tc.stage)
			}
		})
	}
}

// TestMatchOnboardingTriggerStage_LinearProgress_PrefersExtraContext locks
// the precedence: when the FE supplies onboardingNext as authoritative
// state, the linear-progress trigger ("next" / "continue" / "go") routes to
// that hint without needing to scan history. This is the cheap, fast path
// for the most common navigation gesture.
func TestMatchOnboardingTriggerStage_LinearProgress_PrefersExtraContext(t *testing.T) {
	extra := map[string]string{
		"onboardingStage": "overview",
		"onboardingNext":  "choose-your-way",
	}

	for _, input := range []string{"next", "continue", "go", "what's next", "let's continue", "OK next"} {
		t.Run(input, func(t *testing.T) {
			stage, ok := matchOnboardingTriggerStage(input, extra, nil)
			if !ok {
				t.Fatalf("expected match for %q with extraContext hint, got none", input)
			}
			if stage != "choose-your-way" {
				t.Fatalf("stage = %q, want %q", stage, "choose-your-way")
			}
		})
	}
}

// TestMatchOnboardingTriggerStage_LinearProgress_HistoryFallback verifies
// that when the FE doesn't supply onboardingNext, the intercept walks the
// history backwards and uses the latest assistant turn whose stage marker
// carries `next=<id>`. This is the resilience path for clients that don't
// thread onboarding state explicitly.
func TestMatchOnboardingTriggerStage_LinearProgress_HistoryFallback(t *testing.T) {
	history := []ChatTurn{
		{Role: "user", Content: "start onboarding"},
		{Role: "assistant", Content: "[onboarding-stage:overview next=choose-your-way]\n\n# Welcome"},
		{Role: "user", Content: "what is a connector?"},
		{Role: "assistant", Content: "A connector is..."},
	}

	stage, ok := matchOnboardingTriggerStage("next", nil, history)
	if !ok {
		t.Fatalf("expected history-derived match, got none")
	}
	if stage != "choose-your-way" {
		t.Fatalf("stage = %q, want choose-your-way", stage)
	}
}

// TestMatchOnboardingTriggerStage_LinearProgress_TerminalStage pins the
// "no derivable next" path: when the latest assistant marker has no `next=`
// suffix (terminal stage like choose-your-way), a bare "next" trigger must
// NOT short-circuit through the intercept — let the LLM ask a clarifying
// question instead of dispatching onboarding_step with an empty stage.
func TestMatchOnboardingTriggerStage_LinearProgress_TerminalStage(t *testing.T) {
	history := []ChatTurn{
		{Role: "user", Content: "manual"},
		{Role: "assistant", Content: "[onboarding-stage:choose-your-way]\n\n# Pick a path"},
	}

	if stage, ok := matchOnboardingTriggerStage("next", nil, history); ok {
		t.Fatalf("expected ok=false on terminal stage, got stage=%q", stage)
	}
}

// TestMatchOnboardingTriggerStage_LongInputDoesNotMatch is the negative
// guard for Layer 1: anything beyond the bare-trigger length budget falls
// through to the LLM. Without this cap, "Tell me more about the AI option"
// would wrongly dispatch clone-starter-ai because it contains the word
// "AI". The intercept stays narrow on purpose.
func TestMatchOnboardingTriggerStage_LongInputDoesNotMatch(t *testing.T) {
	longInputs := []string{
		"Tell me more about the AI option please",
		"continue with the manual approach using the latest version",
		"please go through the rest of the onboarding steps with me",
	}

	for _, input := range longInputs {
		t.Run(input, func(t *testing.T) {
			if stage, ok := matchOnboardingTriggerStage(input, nil, nil); ok {
				t.Fatalf("expected long input %q to fall through to LLM, intercept fired with stage=%q", input, stage)
			}
		})
	}
}

// TestRedactOnboardingHistory_PreservesNonOnboarding is the surface guard
// for Layer 2: only assistant turns whose first line is a stage marker
// collapse to a placeholder. User turns, regular replies, audit summaries
// and anything else pass through verbatim — Trace must not lose
// conversational context outside the onboarding flow.
func TestRedactOnboardingHistory_PreservesNonOnboarding(t *testing.T) {
	in := []ChatTurn{
		{Role: "user", Content: "start onboarding"},
		{Role: "assistant", Content: "[onboarding-stage:overview next=choose-your-way]\n\n# Welcome\n\nIntro text..."},
		{Role: "user", Content: "what is a connector?"},
		{Role: "assistant", Content: "A connector is the node type that calls a single HTTP endpoint."},
		{Role: "user", Content: "next"},
		{Role: "assistant", Content: "[onboarding-stage:choose-your-way]\n\n# Choose your way"},
	}

	out := redactOnboardingHistory(in)

	if len(out) != len(in) {
		t.Fatalf("length changed: got %d want %d", len(out), len(in))
	}

	if out[0] != in[0] {
		t.Fatalf("user turn 0 was modified: got %#v", out[0])
	}
	if out[2] != in[2] {
		t.Fatalf("user turn 2 was modified: got %#v", out[2])
	}
	if out[3] != in[3] {
		t.Fatalf("non-onboarding assistant turn 3 was modified: got %#v", out[3])
	}
	if out[4] != in[4] {
		t.Fatalf("user turn 4 was modified: got %#v", out[4])
	}

	if strings.Contains(out[1].Content, "[onboarding-stage:") {
		t.Fatalf("turn 1 still leaks the marker the LLM tends to copy:\n%s", out[1].Content)
	}
	if !strings.Contains(out[1].Content, "rendered onboarding step") {
		t.Fatalf("turn 1 placeholder is missing the canonical phrasing:\n%s", out[1].Content)
	}
	if !strings.Contains(out[1].Content, `"overview"`) {
		t.Fatalf("turn 1 placeholder lost the stage id:\n%s", out[1].Content)
	}

	if strings.Contains(out[5].Content, "[onboarding-stage:") {
		t.Fatalf("terminal-stage marker leaked through redaction:\n%s", out[5].Content)
	}
	if !strings.Contains(out[5].Content, `"choose-your-way"`) {
		t.Fatalf("turn 5 placeholder lost the stage id:\n%s", out[5].Content)
	}
}

// TestRedactOnboardingHistory_OriginalNotMutated ensures redactOnboardingHistory
// returns a fresh slice — the caller in handleRequest passes the result of
// snapshotHistory which is itself defensive, but the redaction layer must
// not silently rely on that to keep sess.history untouched.
func TestRedactOnboardingHistory_OriginalNotMutated(t *testing.T) {
	original := []ChatTurn{
		{Role: "assistant", Content: "[onboarding-stage:overview next=choose-your-way]\n\n# Welcome"},
	}
	originalCopy := append([]ChatTurn(nil), original...)

	_ = redactOnboardingHistory(original)

	if original[0].Content != originalCopy[0].Content {
		t.Fatalf("redactOnboardingHistory mutated input: got %q want %q", original[0].Content, originalCopy[0].Content)
	}
}

// TestBuildOnboardingStepEnvelope_Shape pins the canonical JSON wire shape
// for server-initiated dispatches (Layer 1 + Layer 3). The McpManager.run
// dispatch keys on `tool == "onboarding_step"` and `args.stage` exactly as
// the LLM would emit them; deviating from this shape silently demotes the
// tool call to the audit/entity-history fallback and breaks the user.
func TestBuildOnboardingStepEnvelope_Shape(t *testing.T) {
	envelope, raw, err := buildOnboardingStepEnvelope("clone-starter-ai")
	if err != nil {
		t.Fatalf("buildOnboardingStepEnvelope error: %v", err)
	}
	if envelope.Tool != "onboarding_step" {
		t.Fatalf("envelope.Tool = %q, want onboarding_step", envelope.Tool)
	}
	if got, _ := envelope.Args["stage"].(string); got != "clone-starter-ai" {
		t.Fatalf("envelope.Args[stage] = %q, want clone-starter-ai", got)
	}
	if !strings.Contains(string(raw), `"tool":"onboarding_step"`) {
		t.Fatalf("raw envelope missing canonical tool key:\n%s", string(raw))
	}
	if !strings.Contains(string(raw), `"stage":"clone-starter-ai"`) {
		t.Fatalf("raw envelope missing stage arg:\n%s", string(raw))
	}
}

// TestBuildOnboardingStepEnvelope_EmptyStage covers the "start from the
// first stage" call shape (used when the user types "start onboarding"
// without prior context). Args.stage must be ABSENT in that case — passing
// `stage: ""` would trip the OnboardingStepClient input validator.
func TestBuildOnboardingStepEnvelope_EmptyStage(t *testing.T) {
	envelope, raw, err := buildOnboardingStepEnvelope("")
	if err != nil {
		t.Fatalf("buildOnboardingStepEnvelope error: %v", err)
	}
	if envelope.Tool != "onboarding_step" {
		t.Fatalf("envelope.Tool = %q, want onboarding_step", envelope.Tool)
	}
	if _, present := envelope.Args["stage"]; present {
		t.Fatalf("envelope.Args[stage] must be absent for empty stage, got args=%#v", envelope.Args)
	}
	if strings.Contains(string(raw), `"stage"`) {
		t.Fatalf("raw envelope must not carry stage key when empty:\n%s", string(raw))
	}
}
