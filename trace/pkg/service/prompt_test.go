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

func TestBuildSystemPrompt_DocsSearchExamplesWhenRegistered(t *testing.T) {
	actions := []ManifestAction{
		{ID: "processes_timeseries", Title: "Process counts", Kind: "timeseries"},
		{ID: "docs_search", Title: "Search docs", Kind: "docs"},
	}

	prompt := BuildSystemPrompt(actions)

	for _, marker := range []string{
		`"tool":"docs_search"`,
		`"how do I get started"`,
		`"how do I set up OAuth2"`,
		"prefer the docs_search tool over",
	} {
		if !strings.Contains(prompt, marker) {
			t.Fatalf("expected docs_search example %q in prompt, got:\n%s", marker, prompt)
		}
	}
}

func TestBuildSystemPrompt_NoDocsSearchExamplesWhenAbsent(t *testing.T) {
	actions := []ManifestAction{
		{ID: "processes_timeseries", Title: "Process counts", Kind: "timeseries"},
	}

	prompt := BuildSystemPrompt(actions)

	if strings.Contains(prompt, `"tool":"docs_search"`) {
		t.Fatalf("did not expect docs_search examples when tool is not registered, got:\n%s", prompt)
	}
	if strings.Contains(prompt, "prefer the docs_search tool over") {
		t.Fatalf("did not expect docs_search routing instructions when tool is not registered, got:\n%s", prompt)
	}
}

func TestBuildSummariserPrompt_DocsSearchSpecifics(t *testing.T) {
	prompt := BuildSummariserPrompt("docs_search")

	for _, marker := range []string{
		"DOCS_SEARCH SPECIFICS",
		"https://orchesty.io",
		"results is empty",
		"Reply in English by default",
		"bodyExcerpt",
		"NEVER invent",
	} {
		if !strings.Contains(prompt, marker) {
			t.Fatalf("expected docs_search summariser rule %q, got:\n%s", marker, prompt)
		}
	}
}

func TestBuildSummariserPrompt_DocsReadSpecifics(t *testing.T) {
	prompt := BuildSummariserPrompt("docs_read")

	for _, marker := range []string{
		"DOCS_READ SPECIFICS",
		"{path, title, description, body}",
		"https://orchesty.io",
		"NEVER invent",
	} {
		if !strings.Contains(prompt, marker) {
			t.Fatalf("expected docs_read summariser rule %q, got:\n%s", marker, prompt)
		}
	}

	if strings.Contains(prompt, "DOCS_SEARCH SPECIFICS") {
		t.Fatalf("docs_read summariser must not include docs_search-specific rules")
	}
}

func TestBuildSummariserPrompt_OnboardingStepSpecifics(t *testing.T) {
	prompt := BuildSummariserPrompt("onboarding_step")

	for _, marker := range []string{
		"ONBOARDING_STEP SPECIFICS",
		"[onboarding-stage:",
		"[shell]",
		"[prompt]",
		"[link]",
		"VERBATIM",
	} {
		if !strings.Contains(prompt, marker) {
			t.Fatalf("expected onboarding_step summariser rule %q, got:\n%s", marker, prompt)
		}
	}
}

// The STRICT VERBATIM RULE block guards against hallucinated shell commands,
// invented file names (e.g. ".env.example" instead of the real ".env.dist"),
// and the model collapsing a multi-line `[prompt]` action into an ad-hoc
// shell snippet. Locking this in as a test prevents future prompt drift
// from quietly removing the anti-hallucination rules that fixed a real
// regression in the `clone-starter-ai` step.
func TestBuildSummariserPrompt_OnboardingStrictVerbatim(t *testing.T) {
	prompt := BuildSummariserPrompt("onboarding_step")

	for _, marker := range []string{
		"STRICT VERBATIM RULE",
		"actions[] and intro are the single source of truth",
		"NEVER add a shell command, prompt, link",
		"NEVER paraphrase, summarise, reorder",
		"NEVER rewrite the intro",
		".env.dist",
		"worker-ai-starter",
		"BAD example",
	} {
		if !strings.Contains(prompt, marker) {
			t.Fatalf("expected strict-verbatim marker %q, got:\n%s", marker, prompt)
		}
	}
}

// Locks in the anti-summarisation rules added after the build-components
// regression: the model produced a generic "What you do now / Typical flow /
// Before you continue" prose summary instead of rendering the stage payload
// verbatim. Root cause was the generic VOICE & STYLE / RULES blocks
// ("produce a short bullet list", "natural sentences and paragraphs") that
// BuildSummariserPrompt prepends to every other tool. The fix forks a
// dedicated minimal prompt for onboarding_step; this test asserts that the
// dedicated prompt (a) DOES NOT carry the misleading generic VOICE & STYLE /
// RULES prelude over, and (b) DOES carry an explicit list of forbidden
// invented section headings the model previously hallucinated.
func TestBuildSummariserPrompt_OnboardingNoGenericVoiceBlock(t *testing.T) {
	prompt := BuildSummariserPrompt("onboarding_step")

	for _, forbidden := range []string{
		// Generic VOICE & STYLE phrases that pushed the model into prose mode.
		"VOICE & STYLE:",
		"warm, direct",
		"natural sentences and paragraphs",
		// Generic RULES phrases that pushed the model into list mode.
		"produce a short bullet list",
		"timeseries/points result",
	} {
		if strings.Contains(prompt, forbidden) {
			t.Fatalf("onboarding_step summariser must NOT inherit generic prose/list directive %q (it caused the build-components hallucination), got:\n%s", forbidden, prompt)
		}
	}

	for _, required := range []string{
		// Identity: deterministic renderer, not a writer.
		"renderer",
		"NOT a writer",
		"NOT a summariser",
		// Explicit list of section headings the model previously invented.
		"What you do now",
		"Typical flow",
		"What you should check",
		"Before you continue",
		// Explicit ban on closing prose the model previously appended.
		"Make sure the project still builds cleanly",
		// The two anti-pattern code blocks (build-components hallucination +
		// clone-starter-ai regression) live as full BAD examples.
		"Build components",
		"Reasons it's BAD",
		// Anti-rewrite for the intro field (the field the model summarised).
		"Copy it verbatim from the payload",
	} {
		if !strings.Contains(prompt, required) {
			t.Fatalf("expected anti-creative marker %q (locks in the build-components fix), got:\n%s", required, prompt)
		}
	}
}

// The non-onboarding summariser path must STILL carry the generic VOICE &
// STYLE / RULES blocks — those rules are correct for docs / metrics / list
// payloads. The fork only kicks in for onboarding_step. Without this guard
// a future refactor could accidentally strip VOICE & STYLE from every path.
func TestBuildSummariserPrompt_NonOnboardingKeepsGenericPrelude(t *testing.T) {
	for _, toolID := range []string{"docs_search", "docs_read", "processes_timeseries", ""} {
		prompt := BuildSummariserPrompt(toolID)

		for _, marker := range []string{
			"VOICE & STYLE:",
			"natural sentences and paragraphs",
		} {
			if !strings.Contains(prompt, marker) {
				t.Fatalf("non-onboarding summariser (tool %q) must keep generic VOICE & STYLE marker %q, got:\n%s", toolID, marker, prompt)
			}
		}
	}
}

// The marker format must be taught with both GOOD (next= inside the bracket)
// and BAD (next= bleeding outside the bracket) examples so the FE parser
// never has to deal with the bleed in the first place. The parser also
// accepts the BAD form defensively, but the prompt is the primary defence.
func TestBuildSummariserPrompt_OnboardingMarkerExamples(t *testing.T) {
	prompt := BuildSummariserPrompt("onboarding_step")

	for _, marker := range []string{
		"GOOD with next:",
		"[onboarding-stage:clone-starter-ai next=build-components-ai]",
		"GOOD without next:",
		"[onboarding-stage:verify]",
		"BAD (next= leaks as visible text):",
		"[onboarding-stage:clone-starter-ai] next=build-components-ai",
	} {
		if !strings.Contains(prompt, marker) {
			t.Fatalf("expected marker example %q, got:\n%s", marker, prompt)
		}
	}
}

func TestBuildSystemPrompt_DocsReadInstructionsWhenRegistered(t *testing.T) {
	actions := []ManifestAction{
		{ID: "docs_search", Title: "Search docs", Kind: "docs"},
		{ID: "docs_read", Title: "Read doc", Kind: "docs"},
	}

	prompt := BuildSystemPrompt(actions)

	for _, marker := range []string{
		"docs_read",
		"AT MOST ONCE per user turn",
	} {
		if !strings.Contains(prompt, marker) {
			t.Fatalf("expected docs_read instruction %q, got:\n%s", marker, prompt)
		}
	}
}

func TestBuildSystemPrompt_NoDocsReadInstructionsWhenAbsent(t *testing.T) {
	actions := []ManifestAction{
		{ID: "docs_search", Title: "Search docs", Kind: "docs"},
	}

	prompt := BuildSystemPrompt(actions)

	if strings.Contains(prompt, "AT MOST ONCE per user turn") {
		t.Fatalf("did not expect docs_read instructions when tool is absent")
	}
}

func TestBuildSystemPrompt_OnboardingStepExamplesWhenRegistered(t *testing.T) {
	actions := []ManifestAction{
		{ID: "onboarding_step", Title: "Onboarding step", Kind: "onboarding"},
	}

	prompt := BuildSystemPrompt(actions)

	for _, marker := range []string{
		`"tool":"onboarding_step"`,
		"start onboarding",
		"how do I start",
	} {
		if !strings.Contains(prompt, marker) {
			t.Fatalf("expected onboarding_step example %q, got:\n%s", marker, prompt)
		}
	}
}

// Worker-creation intent ("how to create my new worker") must dispatch
// onboarding_step with stage="clone-starter-ai" — the AI branch is the
// default since most users have an AI editor. The stage ships the AI
// bootstrap prompt as copy-paste action cards. docs_search is a poor fit
// here: it would render prose, not an actionable artifact. The block is
// gated behind hasOnboardingStep, so the test wires both docs_search and
// onboarding_step in to mirror the real Trace manifest.
func TestBuildSystemPrompt_OnboardingStepWorkerCreationRouting(t *testing.T) {
	actions := []ManifestAction{
		{ID: "docs_search", Title: "Search docs", Kind: "docs"},
		{ID: "onboarding_step", Title: "Onboarding step", Kind: "onboarding"},
	}

	prompt := BuildSystemPrompt(actions)

	for _, marker := range []string{
		"Worker creation intent",
		`"how to create my new worker"`,
		`"how do I build a worker"`,
		`"how do I scaffold a Node.js worker"`,
		`"can you set up a new worker for me"`,
		`{"tool":"onboarding_step","args":{"stage":"clone-starter-ai"}}`,
		"copy-paste action cards",
		// Registration intent stays on docs_search — the inverse pin
		// keeps Trace from over-routing every worker question through
		// onboarding_step.
		"Worker REGISTRATION / CONNECTION intent",
		"DOES go through docs_search",
		"Connect to an instance page",
	} {
		if !strings.Contains(prompt, marker) {
			t.Fatalf("expected worker-creation routing marker %q, got:\n%s", marker, prompt)
		}
	}
}

// Branch-switch intent: bare "AI" / "manual" triggers must restart the
// matching branch from clone-starter-ai / clone-starter-manual. The two
// branches diverge after the choose-your-way stage and the user can switch
// at any point during the branched stages by typing one of the bare
// triggers. Without this routing, mid-branch users who change their mind
// would have to retype the whole "scaffold a worker" question.
func TestBuildSystemPrompt_BranchSwitchRouting(t *testing.T) {
	actions := []ManifestAction{
		{ID: "docs_search", Title: "Search docs", Kind: "docs"},
		{ID: "onboarding_step", Title: "Onboarding step", Kind: "onboarding"},
	}

	prompt := BuildSystemPrompt(actions)

	for _, marker := range []string{
		"Branch-switch intent",
		"two parallel branches",
		"choose-your-way",
		// AI-branch triggers
		`"AI"`,
		`"ai path"`,
		`"use AI"`,
		`"switch to AI"`,
		`{"tool":"onboarding_step","args":{"stage":"clone-starter-ai"}}`,
		// Manual-branch triggers
		`"manual"`,
		`"manual path"`,
		`"by hand"`,
		`"switch to manual"`,
		`{"tool":"onboarding_step","args":{"stage":"clone-starter-manual"}}`,
		// Restart semantics — always lands on clone-starter-{ai,manual},
		// never on a later stage like build-components-*.
		"the start of the chosen branch",
		// First-time pick from choose-your-way and mid-branch switch use
		// the same routing — the prompt must spell that out so the LLM
		// doesn't gate the routing on prior `onboardingStage`.
		"first-time pick from choose-your-way OR a mid-branch switch",
		// Anti-shortcut rule — without it the LLM was reproducing the
		// previous turn's `[onboarding-stage:...]` marker and intro
		// directly, skipping the tool call and dropping the verbatim
		// prompt action card.
		"ABSOLUTE ANTI-SHORTCUT RULE",
		"NEVER, under any circumstances, emit",
		"[onboarding-stage:",
		"deterministic renderer downstream",
		"copy-paste fidelity",
	} {
		if !strings.Contains(prompt, marker) {
			t.Fatalf("expected branch-switch routing marker %q, got:\n%s", marker, prompt)
		}
	}
}

// Precedence guard: when onboarding_step IS registered, the worker-specific
// docs_search expansion ("build first worker scaffold SDK Node.js PHP AI
// bootstrap manual setup") must NOT appear — it belongs to the fallback
// path for manifests without onboarding_step. The clone-starter-ai routing
// note replaces it. Without this test the two routings could silently
// double-dispatch (LLM sees both, picks one randomly).
func TestBuildSystemPrompt_WorkerExpansionGatedByOnboardingStep(t *testing.T) {
	withOnboarding := BuildSystemPrompt([]ManifestAction{
		{ID: "docs_search", Title: "Search docs", Kind: "docs"},
		{ID: "onboarding_step", Title: "Onboarding step", Kind: "onboarding"},
	})

	if strings.Contains(withOnboarding, `"build first worker scaffold SDK Node.js PHP AI bootstrap manual setup"`) {
		t.Fatalf("worker-specific docs_search expansion must NOT appear when onboarding_step is registered (it would double-dispatch with the clone-starter-ai routing); got:\n%s", withOnboarding)
	}
	// The pivot phrase covers BOTH whole-worker and node-type creation
	// intents — the docs_search section explicitly punts both to the
	// onboarding_step routing block below it.
	if !strings.Contains(withOnboarding, "are NOT docs_search questions") {
		t.Fatalf("expected the docs_search section to point worker-creation AND node-type-creation queries at onboarding_step, got:\n%s", withOnboarding)
	}
	if !strings.Contains(withOnboarding, "node-type creation") {
		t.Fatalf("expected the docs_search section to call out node-type creation explicitly, got:\n%s", withOnboarding)
	}
	// The per-noun docs_search expansion examples (connector, batch,
	// custom node, application) must NOT leak through when onboarding_step
	// owns those intents — otherwise the LLM sees both the docs_search
	// expansion *and* the onboarding_step routing for the same word and
	// regresses to the docs_search definition path. The gating keeps the
	// system prompt's mapping unambiguous.
	for _, leaked := range []string{
		`"connector build first worker SDK get started"`,
		`"batch build first worker SDK get started"`,
		`"custom node build first worker SDK get started"`,
		`"application build first worker SDK get started"`,
	} {
		if strings.Contains(withOnboarding, leaked) {
			t.Fatalf("per-noun docs_search expansion %q must be gated off when onboarding_step is registered (the onboarding_step routing block owns those intents); got:\n%s", leaked, withOnboarding)
		}
	}

	// Without onboarding_step, the docs_search fallback expansion must
	// still be present so Trace has *something* to surface for users who
	// disabled the onboarding wizard tool.
	withoutOnboarding := BuildSystemPrompt([]ManifestAction{
		{ID: "docs_search", Title: "Search docs", Kind: "docs"},
	})

	if !strings.Contains(withoutOnboarding, `"build first worker scaffold SDK Node.js PHP AI bootstrap manual setup"`) {
		t.Fatalf("worker-specific docs_search expansion must still be the fallback when onboarding_step is absent, got:\n%s", withoutOnboarding)
	}
	if strings.Contains(withoutOnboarding, "are NOT docs_search questions") {
		t.Fatalf("onboarding_step pivot note must not leak into the prompt when onboarding_step is absent, got:\n%s", withoutOnboarding)
	}
	// And conversely the per-noun expansion examples MUST be present in
	// the no-onboarding-step fallback so Trace has the docs-grounded path
	// for those queries.
	for _, expected := range []string{
		`"connector build first worker SDK get started"`,
		`"batch build first worker SDK get started"`,
		`"custom node build first worker SDK get started"`,
		`"application build first worker SDK get started"`,
	} {
		if !strings.Contains(withoutOnboarding, expected) {
			t.Fatalf("per-noun docs_search expansion %q must be the fallback when onboarding_step is absent, got:\n%s", expected, withoutOnboarding)
		}
	}
}

func TestBuildSystemPrompt_NoOnboardingExamplesWhenAbsent(t *testing.T) {
	actions := []ManifestAction{
		{ID: "processes_timeseries", Title: "Process counts", Kind: "timeseries"},
	}

	prompt := BuildSystemPrompt(actions)

	if strings.Contains(prompt, `"tool":"onboarding_step"`) {
		t.Fatalf("did not expect onboarding_step examples when tool is absent")
	}
}

func TestBuildSummariserPrompt_NoDocsSpecificsForOtherTools(t *testing.T) {
	prompt := BuildSummariserPrompt("processes_timeseries")

	if strings.Contains(prompt, "DOCS_SEARCH SPECIFICS") {
		t.Fatalf("did not expect docs-specific summariser rules for processes_timeseries, got:\n%s", prompt)
	}
}

// Voice & Style block must precede the rule list and the per-tool sections so
// the persona ("you ARE Orchesty's built-in guide") frames every output. The
// block is shared across all tools (no per-tool gating).
// VOICE & STYLE block must be present for the prose-summarising tools
// (docs_search, docs_read, metrics, generic). It is intentionally absent
// from the onboarding_step path — see TestBuildSummariserPrompt_OnboardingNoGenericVoiceBlock
// for the locked-in reasoning. The fork was introduced to fix the
// build-components hallucination where VOICE & STYLE (\"natural sentences\",
// \"warm, direct\") and generic RULES (\"produce a short bullet list\")
// pushed the summariser into rewriting stage payloads as prose.
func TestBuildSummariserPrompt_VoiceAndStyleBlockPresent(t *testing.T) {
	for _, toolID := range []string{"", "processes_timeseries", "docs_search", "docs_read"} {
		prompt := BuildSummariserPrompt(toolID)

		for _, marker := range []string{
			"VOICE & STYLE",
			"You ARE Orchesty's built-in guide",
			"Speak AS the product",
			"never bluffs",
			"FORBIDDEN PHRASES",
		} {
			if !strings.Contains(prompt, marker) {
				t.Fatalf("tool %q: expected voice marker %q, got:\n%s", toolID, marker, prompt)
			}
		}

		voiceIdx := strings.Index(prompt, "VOICE & STYLE")
		rulesIdx := strings.Index(prompt, "RULES:")
		if voiceIdx < 0 || rulesIdx < 0 || voiceIdx >= rulesIdx {
			t.Fatalf("tool %q: voice block must come before RULES, got voice=%d rules=%d", toolID, voiceIdx, rulesIdx)
		}
	}
}

// Forbidden phrases (citation-style references to docs, third-person framing
// of the product) must appear only inside the FORBIDDEN PHRASES list — they
// are listed there as anti-examples for the model. The point of this test is
// to lock down the banned-phrase set so a future prompt edit cannot quietly
// reintroduce a "Per the docs" instruction in a positive context. The whole
// prompt is English-only by policy, so banned variants are EN-only too.
func TestBuildSummariserPrompt_ForbiddenPhrasesEnumerated(t *testing.T) {
	prompt := BuildSummariserPrompt("docs_search")

	bannedIdx := strings.Index(prompt, "FORBIDDEN PHRASES")
	if bannedIdx < 0 {
		t.Fatalf("expected a FORBIDDEN PHRASES section in the voice block, got:\n%s", prompt)
	}

	for _, phrase := range []string{
		`"Per the docs"`,
		`"According to the docs"`,
		`"The documentation says"`,
		`"I found in the docs"`,
		`"This page explains"`,
		`"The page describes"`,
		`"Orchesty is an integration platform that…"`,
		`"Orchesty allows you to…"`,
	} {
		idx := strings.Index(prompt, phrase)
		if idx < 0 {
			t.Fatalf("expected forbidden phrase %s to be enumerated in the voice block, got:\n%s", phrase, prompt)
		}
		if idx < bannedIdx {
			t.Fatalf("forbidden phrase %s appears before the FORBIDDEN PHRASES marker — that suggests it was instructed positively somewhere, prompt:\n%s", phrase, prompt)
		}
	}
}

// HOW-TO query expansion in BuildSystemPrompt steers docs_search toward
// foundation pages (Get Started, Workers and Components) for "how do I build
// X" intents while leaving "what is X" queries forwarded verbatim. Without
// the expansion, Trace surfaces only topic-specific reference pages and ends
// up answering with definitions instead of action plans. The test locks down
// both halves: the expansion examples for the build/create intent AND the
// explicit do-not-expand directive for definitional intents.
func TestBuildSystemPrompt_HowToQueryExpansion(t *testing.T) {
	actions := []ManifestAction{
		{ID: "docs_search", Title: "Search docs", Kind: "docs"},
	}

	prompt := BuildSystemPrompt(actions)

	for _, marker := range []string{
		"HOW-TO QUERY EXPANSION",
		"foundation pages (Get Started, Workers and Components)",
		"build first worker SDK get started",
		`"connector build first worker SDK get started"`,
		`"batch build first worker SDK get started"`,
		`"custom node build first worker SDK get started"`,
		`"application build first worker SDK get started"`,
		// Worker-specific expansion: must surface the scaffold page, not
		// the registration page. The query carries explicit scaffold
		// keywords (Node.js, PHP, AI bootstrap, manual setup) that score
		// against build-your-first-worker.md and de-prioritise the
		// connect-to-instance page.
		"worker-specific expansion",
		"\"Build your first worker\"",
		`"build first worker scaffold SDK Node.js PHP AI bootstrap manual setup"`,
		"DO NOT expand",
	} {
		if !strings.Contains(prompt, marker) {
			t.Fatalf("expected query-expansion marker %q, got:\n%s", marker, prompt)
		}
	}

	// The verbatim/expansion split must keep the verbatim rule before the
	// expansion exception so the model treats expansion as the gated case,
	// not the default.
	verbatimIdx := strings.Index(prompt, "forward the user's wording as `query`")
	expansionIdx := strings.Index(prompt, "HOW-TO QUERY EXPANSION")
	if verbatimIdx < 0 || expansionIdx < 0 || verbatimIdx >= expansionIdx {
		t.Fatalf("expected verbatim rule before HOW-TO expansion block, got verbatim=%d expansion=%d", verbatimIdx, expansionIdx)
	}
}

// HOW-TO INTENT FORMAT in the docs_search summariser is the second half of
// the pair: query expansion gets the right pages into bodyExcerpt, this
// block tells the model to RENDER them as a numbered outline + walk-through
// offer instead of a textbook paragraph. Both halves must stay in lockstep —
// expansion without format reverts to definitions, format without expansion
// has no foundation excerpts to ground the outline.
func TestBuildSummariserPrompt_HowToIntentFormat(t *testing.T) {
	prompt := BuildSummariserPrompt("docs_search")

	for _, marker := range []string{
		"HOW-TO INTENT FORMAT",
		"NUMBERED OUTLINE of 4-7 single-line steps",
		"foundation pages",
		"`/learn/get-started/...`",
		"`/learn/basics/workers-and-components`",
		"NEVER invent steps",
		"ONE foundation page",
		"ONE topic-specific reference page",
		`"Want me to walk you through any of these in detail?"`,
		// CREATE-BEFORE-CONNECT pins the lifecycle order so Trace cannot
		// regress to leading with "Settings -> Workers -> Add Worker"
		// before the user actually has a worker project on disk.
		"CREATE-BEFORE-CONNECT ORDERING",
		"pick the SDK / scaffold / install / verify the build FIRST",
		"register / connect to the instance / wire",
		"Never lead with \"go to Settings",
		"Skip this format only when the question is clearly definitional",
	} {
		if !strings.Contains(prompt, marker) {
			t.Fatalf("expected HOW-TO format marker %q, got:\n%s", marker, prompt)
		}
	}

	// The HOW-TO block must come before the EXAMPLES block so the outline
	// rules frame the few-shot examples (and not the other way round).
	howToIdx := strings.Index(prompt, "HOW-TO INTENT FORMAT")
	examplesIdx := strings.Index(prompt, "EXAMPLES (style only")
	if howToIdx < 0 || examplesIdx < 0 || howToIdx >= examplesIdx {
		t.Fatalf("HOW-TO format block must precede EXAMPLES, got how-to=%d examples=%d", howToIdx, examplesIdx)
	}
}

// The HOW-TO few-shot needs both a GOOD example showing the canonical
// outline + walk-through-offer shape AND a BAD anti-example showing the
// definition-first failure mode the prompt is fixing. Without the BAD
// pair the model often regresses to "A connector is a node type that…"
// boilerplate when it scrapes a definitional bodyExcerpt.
func TestBuildSummariserPrompt_DocsSearchHowToFewShot(t *testing.T) {
	prompt := BuildSummariserPrompt("docs_search")

	for _, marker := range []string{
		// Connector few-shot: outline + walk-through offer.
		"GOOD (HOW-TO format,",
		"To build your own connector you go through these steps",
		"1. Spin up a worker",
		"2. Add an Application class",
		"3. Extend `AConnector`",
		"4. Register both in the worker entry point",
		"5. Wire the connector into a topology",
		"[Get Started](https://orchesty.io/learn/get-started)",
		"[Connectors](https://orchesty.io/docs/2.0/development/building-nodes/connectors)",
		"Want me to walk you through any of these in detail?",
		"BAD (HOW-TO answered as definition):",
		"A connector is a node type that makes a single HTTP call",
		// Worker few-shot: explicit CREATE-BEFORE-CONNECT example with
		// the registration-only anti-pattern. This is the regression test
		// for "How to create my new worker" Trace previously answered as
		// pure registration flow.
		"how to create my new worker",
		"CREATE-BEFORE-CONNECT ordering",
		"To build a new worker you go through these steps",
		"1. Pick an SDK",
		"2. Scaffold the project",
		"3. Install dependencies",
		"4. Verify the build",
		"5. Register the worker in the Admin UI",
		"[Build your first worker](https://orchesty.io/learn/get-started/build-your-first-worker)",
		"[Connect to an instance](https://orchesty.io/docs/2.0/getting-started/worker-setup/connect-to-instance)",
		"answered as registration-only",
		"To connect a worker, go to Settings",
	} {
		if !strings.Contains(prompt, marker) {
			t.Fatalf("expected HOW-TO few-shot marker %q, got:\n%s", marker, prompt)
		}
	}
}

// docs_search EXAMPLES must teach the persona by example: the GOOD lines use
// second-person prose and inline Markdown links; the BAD lines explicitly
// include the forbidden citation phrasings ("Per the docs…", "I found in
// the docs…") and the third-person framing. Without the BAD anti-examples
// the model tends to revert to "Per the docs…" boilerplate. Examples are
// English-only by policy.
func TestBuildSummariserPrompt_DocsSearchExamplesTeachPersona(t *testing.T) {
	prompt := BuildSummariserPrompt("docs_search")

	for _, marker := range []string{
		"You set up OAuth2",
		"we'll handle the redirect flow",
		"You create a Connector",
		"Find the details in",
		"Per the docs, OAuth2 is configured",
		"I found in the docs that a connector",
		"Orchesty is an integration platform that",
	} {
		if !strings.Contains(prompt, marker) {
			t.Fatalf("expected docs_search example marker %q, got:\n%s", marker, prompt)
		}
	}

	goodIdx := strings.Index(prompt, "- GOOD:")
	badIdx := strings.Index(prompt, "- BAD:")
	if goodIdx < 0 || badIdx < 0 || goodIdx >= badIdx {
		t.Fatalf("expected GOOD examples to come before BAD examples, got good=%d bad=%d", goodIdx, badIdx)
	}
}

// The system-prompt persona block is what keeps Trace from falling back to
// "I am the Orchesty Trace assistant" framing. Test that the PERSONA header,
// the second-person directive, and the silent-tool-call rule are all
// present. The prompt is English-only by policy.
func TestBuildSystemPrompt_PersonaBlockPresent(t *testing.T) {
	prompt := BuildSystemPrompt(nil)

	for _, marker := range []string{
		"PERSONA",
		"you ARE Orchesty's built-in guide",
		"second person",
		"INTERNAL memory",
		"NEVER tell the user you searched docs",
	} {
		if !strings.Contains(prompt, marker) {
			t.Fatalf("expected persona marker %q in system prompt, got:\n%s", marker, prompt)
		}
	}
}

// English-only policy: the prompts must explicitly default to English and
// must not contain any non-ASCII letters (a quick proxy for "no Czech, no
// other diacritic-using languages"). Em-dash "—" and curly punctuation are
// allowed; we look only for letter-like diacritics.
func TestBuildPrompts_EnglishOnly(t *testing.T) {
	for _, prompt := range []string{
		BuildSystemPrompt(nil),
		BuildSystemPrompt([]ManifestAction{
			{ID: "docs_search", Title: "Search docs", Kind: "docs"},
			{ID: "docs_read", Title: "Read doc", Kind: "docs"},
			{ID: "onboarding_step", Title: "Onboarding step", Kind: "onboarding"},
		}),
		BuildSummariserPrompt(""),
		BuildSummariserPrompt("processes_timeseries"),
		BuildSummariserPrompt("docs_search"),
		BuildSummariserPrompt("docs_read"),
		BuildSummariserPrompt("onboarding_step"),
	} {
		for _, r := range prompt {
			if (r >= 'A' && r <= 'Z') || (r >= 'a' && r <= 'z') || r < 128 {
				continue
			}
			// Letter-like diacritic from a non-English alphabet. Punctuation,
			// arrows, em-dashes etc. are all <128 or in the 0x2000–0x27FF
			// symbol blocks; we only reject letters.
			if (r >= 0x00C0 && r <= 0x024F) || (r >= 0x0370 && r <= 0x04FF) {
				t.Fatalf("non-English letter %q (U+%04X) leaked into prompt — policy is English-only:\n%s", r, r, prompt)
			}
		}
	}

	for _, marker := range []string{
		"default reply language is English",
		"DEFAULT REPLY LANGUAGE = English",
	} {
		var found bool
		for _, prompt := range []string{
			BuildSystemPrompt(nil),
			BuildSummariserPrompt("docs_search"),
		} {
			if strings.Contains(prompt, marker) {
				found = true
				break
			}
		}
		if !found {
			t.Fatalf("expected an English-default directive containing %q in the prompts", marker)
		}
	}
}

// Grounding-obligation phrasing in docs_search and docs_read forces the model
// to tie concrete claims (feature names, paths, flags) back to the tool
// payload. Tested as substrings so prompt wording can drift without breaking
// the test, as long as the obligation survives.
func TestBuildSummariserPrompt_DocsSearchGroundingAndLinks(t *testing.T) {
	prompt := BuildSummariserPrompt("docs_search")

	for _, marker := range []string{
		"Grounding:",
		"verbatim in `bodyExcerpt`",
		"LINKS:",
		"[Title](https://orchesty.io<path>)",
		"NEVER style page titles with backticks",
		"EXAMPLES",
		"GOOD:",
		"BAD:",
	} {
		if !strings.Contains(prompt, marker) {
			t.Fatalf("expected docs_search prompt to include %q, got:\n%s", marker, prompt)
		}
	}
}

func TestBuildSummariserPrompt_DocsReadGroundingAndLinks(t *testing.T) {
	prompt := BuildSummariserPrompt("docs_read")

	for _, marker := range []string{
		"Grounding:",
		"verbatim in `body`",
		"LINKS:",
		"[Title](https://orchesty.io<path>)",
		"backticks for titles",
	} {
		if !strings.Contains(prompt, marker) {
			t.Fatalf("expected docs_read prompt to include %q, got:\n%s", marker, prompt)
		}
	}
}

// The system prompt must allow general conceptual answers (so the bot stops
// hard-refusing "what is a workflow") while still demanding grounding for
// concrete platform-specific claims. Both halves are checked together to
// guarantee the policy stays balanced.
func TestBuildSystemPrompt_AllowsGeneralAnswersWithGrounding(t *testing.T) {
	prompt := BuildSystemPrompt(nil)

	for _, marker := range []string{
		"general conceptual questions",
		"beginner-friendly",
		"MUST come from a tool result",
		"never invent",
	} {
		if !strings.Contains(prompt, marker) {
			t.Fatalf("expected balanced grounding policy marker %q, got:\n%s", marker, prompt)
		}
	}
}

func TestContainsActionID(t *testing.T) {
	actions := []ManifestAction{{ID: "a"}, {ID: "b"}, {ID: "docs_search"}}

	if !containsActionID(actions, "docs_search") {
		t.Fatalf("expected containsActionID to find docs_search")
	}
	if containsActionID(actions, "missing") {
		t.Fatalf("expected containsActionID to miss unknown id")
	}
	if containsActionID(nil, "docs_search") {
		t.Fatalf("expected containsActionID to return false for nil slice")
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

// TestBuildSystemPrompt_NodeTypeRoutingIntents pins the add-a-node mapping so
// every node-type stage shipped under content/onboarding/ has its trigger
// phrase and the matching onboarding_step envelope in the system prompt.
// Without this test the LLM tends to fall back to docs_search for any
// "how to add X" intent (definitions instead of actionable cards).
func TestBuildSystemPrompt_NodeTypeRoutingIntents(t *testing.T) {
	actions := []ManifestAction{
		{ID: "docs_search", Title: "Search docs", Kind: "docs"},
		{ID: "onboarding_step", Title: "Onboarding step", Kind: "onboarding"},
	}

	prompt := BuildSystemPrompt(actions)

	cases := []struct {
		trigger string
		stage   string
	}{
		{`"add / write / build / make a connector"`, "connector-node"},
		{`"add / write / build / make a batch"`, "batch-node"},
		{`"add / write / build / make a custom node"`, "custom-node"},
		// All four auth flavors collapse onto the single `application`
		// stage — the agent picks OAuth2 / Basic / client-credentials /
		// no-auth from the API docs. The trigger phrase enumerates them
		// so the prompt still recognises explicit-flavor wording.
		{`"add / write / build / make / set up an application"`, "application"},
		{`"add an OAuth2 application"`, "application"},
		{`"add a Basic-auth application"`, "application"},
		{`"add a client-credentials application"`, "application"},
		{`"add a no-auth application"`, "application"},
		{`"add / set up / make a webhook trigger"`, "webhook-trigger"},
		{`"add / set up / make an event trigger"`, "event-trigger"},
		{`"add / set up / make a cron trigger"`, "cron-trigger"},
	}

	for _, c := range cases {
		if !strings.Contains(prompt, c.trigger) {
			t.Fatalf("missing add-a-node trigger phrase %q in prompt:\n%s", c.trigger, prompt)
		}
		envelope := `{"tool":"onboarding_step","args":{"stage":"` + c.stage + `"}}`
		if !strings.Contains(prompt, envelope) {
			t.Fatalf("missing onboarding_step envelope for %q (expected stage=%q):\n%s", c.trigger, c.stage, prompt)
		}
	}

	// The Add-a-node intent header itself must be present so the LLM
	// scopes the mapping correctly.
	if !strings.Contains(prompt, "Add-a-node intent") {
		t.Fatalf("expected 'Add-a-node intent' header in prompt:\n%s", prompt)
	}
}

// TestBuildSystemPrompt_NodeCreationDoesNotFallThroughToCloneStarter locks
// down the regression that triggered the whole node-type how-to family:
// before the fix, "how I can write a new batch" routed to clone-starter-ai
// (worker scaffold) instead of the batch-node stage. The BAD example must
// be present verbatim in the prompt so the LLM has the explicit anti-pattern
// to compare against.
func TestBuildSystemPrompt_NodeCreationDoesNotFallThroughToCloneStarter(t *testing.T) {
	prompt := BuildSystemPrompt([]ManifestAction{
		{ID: "docs_search", Title: "Search docs", Kind: "docs"},
		{ID: "onboarding_step", Title: "Onboarding step", Kind: "onboarding"},
	})

	for _, marker := range []string{
		`"I want to know how I can write a new batch"`,
		"add-a-node intent",
		"NOT a worker-creation intent",
		"MUST NOT route to clone-starter-ai",
		`{"tool":"onboarding_step","args":{"stage":"batch-node"}}`,
		// Defensive: the prompt must spell out that the substring "new"
		// alone does not promote a node-type intent into worker scaffolding.
		`The substring "new" by itself`,
		`"new worker"`,
		// Webhook BAD example — same shape, different node type. Pinning
		// both protects against the LLM treating the BAD pattern as a
		// one-off for batches only.
		`"how do I make a webhook trigger?"`,
		`{"tool":"onboarding_step","args":{"stage":"webhook-trigger"}}`,
	} {
		if !strings.Contains(prompt, marker) {
			t.Fatalf("missing anti-regression marker %q in prompt:\n%s", marker, prompt)
		}
	}
}

// TestBuildSystemPrompt_WorkerCreationStillRoutesToCloneStarter is the
// counter-regression test for the new node-type routing block: the
// existing worker-scaffold examples must remain verbatim, so adding the
// add-a-node fork doesn't accidentally erase the worker-creation routing.
func TestBuildSystemPrompt_WorkerCreationStillRoutesToCloneStarter(t *testing.T) {
	prompt := BuildSystemPrompt([]ManifestAction{
		{ID: "docs_search", Title: "Search docs", Kind: "docs"},
		{ID: "onboarding_step", Title: "Onboarding step", Kind: "onboarding"},
	})

	for _, marker := range []string{
		"Worker creation intent",
		`"how to create my new worker"`,
		`"how do I build a worker"`,
		`"how do I scaffold a Node.js worker"`,
		`"can you set up a new worker for me"`,
		`{"tool":"onboarding_step","args":{"stage":"clone-starter-ai"}}`,
	} {
		if !strings.Contains(prompt, marker) {
			t.Fatalf("worker-creation routing regressed: missing %q in prompt:\n%s", marker, prompt)
		}
	}
}

// TestBuildSystemPrompt_GenericNodeIntentRoutesToHub asserts that the
// catch-all "add a node" / "what node types are there" / "what's next
// after I scaffolded a worker" intents land on the add-a-node hub stage,
// not on a randomly-picked node-type stage. The hub is the linear
// continuation of verify and the discovery surface for piecemeal node
// additions later in the integration's life.
func TestBuildSystemPrompt_GenericNodeIntentRoutesToHub(t *testing.T) {
	prompt := BuildSystemPrompt([]ManifestAction{
		{ID: "docs_search", Title: "Search docs", Kind: "docs"},
		{ID: "onboarding_step", Title: "Onboarding step", Kind: "onboarding"},
	})

	for _, marker := range []string{
		`"add a node"`,
		`"add a component"`,
		`"what node types are there"`,
		`"what's next after I scaffolded a worker"`,
		`{"tool":"onboarding_step","args":{"stage":"add-a-node"}}`,
		"the hub stage that lists every node-type how-to",
	} {
		if !strings.Contains(prompt, marker) {
			t.Fatalf("missing generic-node-hub marker %q in prompt:\n%s", marker, prompt)
		}
	}
}

// TestBuildSummariserPrompt_OnboardingClosingLineLiteral pins the rendered
// closing line for staged onboarding cards. The summariser is what the
// LLM uses to render an onboarding_step result, and the last line of
// every card with `next` set is the user-visible CTA.
//
// Two guards:
//  1. The new literal "Reply `next` when you're ready to continue." MUST
//     appear — any drift here changes the product wording.
//  2. The legacy "ask me \"what's next\"" wording MUST NOT come back.
//     Users were not actually typing "what's next" (it reads weird and
//     requires apostrophes / quotes), which is why we rewrote it to the
//     shorter "Reply `next`" CTA. Keep this test as a guardrail against
//     a careless revert.
func TestBuildSummariserPrompt_OnboardingClosingLineLiteral(t *testing.T) {
	prompt := BuildSummariserPrompt("onboarding_step")

	const wantLiteral = "Reply `next` when you're ready to continue."
	if !strings.Contains(prompt, wantLiteral) {
		t.Fatalf("expected closing-line literal %q in onboarding summariser prompt, got:\n%s", wantLiteral, prompt)
	}
	for _, banned := range []string{
		`Next: ask me "what's next".`,
		`ask me "what's next"`,
	} {
		if strings.Contains(prompt, banned) {
			t.Fatalf("legacy CTA wording %q must not appear in onboarding summariser prompt; rewrite it to %q", banned, wantLiteral)
		}
	}
}

// TestBuildSystemPrompt_OnboardingStepRecognisesBareNext makes sure the
// detection-pattern list for the onboarding_step tool routes the new
// short user inputs ("next", "go", "continue") at the routing block
// where the LLM resolves to onboarding_step with the stored stage. The
// rewrite shortened the closing line to just "Reply `next`", so the
// detection list MUST also accept those bare words — otherwise users
// would type the prompted word and Trace wouldn't move forward.
func TestBuildSystemPrompt_OnboardingStepRecognisesBareNext(t *testing.T) {
	prompt := BuildSystemPrompt([]ManifestAction{
		{ID: "onboarding_step", Title: "Onboarding step", Kind: "onboarding"},
	})

	for _, marker := range []string{
		`"next"`,
		`"go"`,
		`"continue"`,
		`bare "next" / "go" / "continue" / "what's next"`,
	} {
		if !strings.Contains(prompt, marker) {
			t.Fatalf("expected bare-next detection marker %q, got:\n%s", marker, prompt)
		}
	}
}
