package service

import (
	"encoding/json"
	"fmt"
	"regexp"
	"strings"
)

// onboardingStageMarkerRe matches the deterministic stage marker emitted by
// renderOnboardingStep on the FIRST line of a card. Both the unstable
// (without `next=`) and stable (with `next=<id>`) shapes are accepted so we
// can re-extract `next` from the latest assistant turn when the user types a
// linear-progression trigger ("next", "continue", ...).
//
// Examples:
//   [onboarding-stage:overview next=choose-your-way]
//   [onboarding-stage:choose-your-way]                 // terminal — no next
//   [onboarding-stage:add-a-node next=connector-node]
var onboardingStageMarkerRe = regexp.MustCompile(`^\[onboarding-stage:([a-z0-9-]+)(?:\s+next=([a-z0-9-]+))?\]`)

// parseOnboardingStageMarker extracts (stage, next) from the first line of a
// chat turn's content if it carries the deterministic stage marker. The
// `next` return value is empty for terminal stages (e.g. choose-your-way,
// per-node-type leaves). ok=false for any non-onboarding text.
//
// The matcher is intentionally line-anchored — we never want to misclassify
// quoted text or a marker-shaped fragment buried in prose as a real stage
// marker (the renderer always emits it on the first line).
func parseOnboardingStageMarker(content string) (stage, next string, ok bool) {
	trimmed := strings.TrimSpace(content)
	if trimmed == "" {
		return "", "", false
	}

	line := trimmed
	if idx := strings.IndexByte(trimmed, '\n'); idx >= 0 {
		line = trimmed[:idx]
	}

	matches := onboardingStageMarkerRe.FindStringSubmatch(line)
	if len(matches) == 0 {
		return "", "", false
	}

	stage = matches[1]
	if len(matches) >= 3 {
		next = matches[2]
	}

	return stage, next, true
}

// triggerMaxLen caps the user-input length the bare-trigger intercept will
// consider. Anything longer is treated as a real question and routed
// through the LLM (with Layer 2 history redaction + Layer 3 post-guard
// still in play). Tuned to fit the longest legitimate trigger phrases
// like "switch to manual" / "let's continue" with comfortable headroom.
const triggerMaxLen = 40

// aiTriggers / manualTriggers / linearProgressTriggers enumerate the bare
// onboarding inputs Trace recognises BEFORE invoking the LLM. They mirror the
// trigger lists baked into prompt.go so the LLM and the server-side intercept
// agree on what counts as navigation. Keep them in sync — adding a trigger
// here without updating prompt.go (or vice versa) creates an asymmetry where
// the LLM thinks it's responsible for a phrase the server already handled.
//
// All keys are lowercased, trimmed, and stripped of trailing punctuation
// (see normaliseTriggerInput). Keep entries in that canonical form.
var aiTriggers = map[string]struct{}{
	"ai":             {},
	"a.i.":           {},
	"a.i":            {},
	"ai path":        {},
	"the ai path":    {},
	"use ai":         {},
	"with ai":        {},
	"switch to ai":   {},
	"i want ai":      {},
	"ai please":      {},
	"let's go ai":    {},
	"lets go ai":     {},
	"go ai":          {},
	"go with ai":     {},
	"give me the ai": {},
	"ai way":         {},
	"the ai way":     {},
}

var manualTriggers = map[string]struct{}{
	"manual":             {},
	"manually":           {},
	"manual path":        {},
	"the manual path":    {},
	"no ai":              {},
	"by hand":            {},
	"switch to manual":   {},
	"i want manual":      {},
	"manual please":      {},
	"go manual":          {},
	"go with manual":     {},
	"give me the manual": {},
	"manual way":         {},
	"the manual way":     {},
}

var linearProgressTriggers = map[string]struct{}{
	"next":                {},
	"next step":           {},
	"the next step":       {},
	"continue":            {},
	"continue onboarding": {},
	"let's continue":      {},
	"lets continue":       {},
	"go":                  {},
	"go on":               {},
	"let's go":            {},
	"lets go":             {},
	"proceed":             {},
	"keep going":          {},
	"move on":             {},
	"carry on":            {},
	"what's next":         {},
	"whats next":          {},
	"what is next":        {},
	"what next":           {},
	"ok next":             {},
	"okay next":           {},
}

// normaliseTriggerInput collapses the user's raw input to the canonical key
// shape used by the trigger maps: lowercase, no surrounding whitespace,
// trailing punctuation stripped. So "Continue!", "  CONTINUE  " and
// "continue?" all match the canonical "continue" entry.
//
// We DO NOT normalise interior whitespace (e.g. "next   step" stays as
// "next   step") — anything past basic capitalisation/punctuation noise is
// considered a long-form input and routed through the LLM where the
// system prompt + Layer 2 redaction take over.
func normaliseTriggerInput(input string) string {
	s := strings.ToLower(strings.TrimSpace(input))
	s = strings.TrimRight(s, ".!?,;:")

	return strings.TrimSpace(s)
}

// matchOnboardingTriggerStage maps a user message to a target onboarding
// stage when the input is a recognised bare trigger AND a stage can be
// derived. Returns ("", false) for everything else, which is the signal to
// fall through to the LLM-driven path.
//
// Routing rules:
//   - AI triggers          → "clone-starter-ai" (always — first-time pick
//                            from choose-your-way and mid-branch switch
//                            collapse to the same restart point).
//   - manual triggers      → "clone-starter-manual" (symmetric).
//   - linear progress      → derived from the FE-supplied
//                            extraContext["onboardingNext"] hint, or by
//                            scanning history backwards for the latest
//                            assistant turn carrying `next=<id>` in its
//                            stage marker. Returns ok=false if neither
//                            source yields a target — that means we are on
//                            a terminal stage where "next" is genuinely
//                            ambiguous; let the LLM ask a follow-up.
//
// The history scan only ever inspects Role=="assistant" turns and only the
// FIRST line of each. Quoted markers buried in prose (e.g. a docs_search
// snippet) cannot accidentally redirect the user.
func matchOnboardingTriggerStage(
	input string,
	extraContext map[string]string,
	history []ChatTurn,
) (string, bool) {
	norm := normaliseTriggerInput(input)
	if norm == "" || len(norm) > triggerMaxLen {
		return "", false
	}

	if _, ok := aiTriggers[norm]; ok {
		return "clone-starter-ai", true
	}

	if _, ok := manualTriggers[norm]; ok {
		return "clone-starter-manual", true
	}

	if _, ok := linearProgressTriggers[norm]; ok {
		if hint := strings.TrimSpace(extraContext["onboardingNext"]); hint != "" {
			return hint, true
		}

		for i := len(history) - 1; i >= 0; i-- {
			turn := history[i]
			if turn.Role != "assistant" {
				continue
			}
			if _, next, ok := parseOnboardingStageMarker(turn.Content); ok && next != "" {
				return next, true
			}
		}

		return "", false
	}

	return "", false
}

// buildOnboardingStepEnvelope assembles a synthetic {tool, args} envelope
// for a server-initiated onboarding_step dispatch. We need both the parsed
// chatEnvelope (so handleToolEnvelope's logging/dispatch works) and the
// canonical JSON byte slice (RunAction signs the request body verbatim).
//
// The shape MUST match what the LLM would have emitted: top-level `tool`
// and `args` keys, args defaulting to an empty object when stage is empty.
func buildOnboardingStepEnvelope(stage string) (chatEnvelope, []byte, error) {
	args := map[string]interface{}{}
	if stage != "" {
		args["stage"] = stage
	}

	payload := map[string]interface{}{
		"tool": "onboarding_step",
		"args": args,
	}

	raw, err := json.Marshal(payload)
	if err != nil {
		return chatEnvelope{}, nil, fmt.Errorf("encode onboarding envelope: %w", err)
	}

	return chatEnvelope{Tool: "onboarding_step", Args: args}, raw, nil
}

// redactOnboardingHistory replaces every assistant turn whose first line is
// a [onboarding-stage:...] marker with a compact placeholder. LLMs tend to
// pattern-match against the most structurally distinctive thing they can see
// in the turn history, and the deterministic stage marker is exactly that:
// a very copyable scaffold that cues the model into reproducing the next
// stage's text inline instead of calling onboarding_step. Stripping the
// marker (and the rendered card body that follows it) denies the shortcut
// without losing the conversational thread — we keep the placeholder so the
// LLM still knows the user has seen that step.
//
// The original turns stay in sess.history untouched; this helper operates on
// a snapshot only. That keeps debug dumps / future "resume chat" features
// faithful to what the user actually saw.
//
// Non-onboarding turns (Reply text, audit summaries, raw tool envelopes)
// pass through unchanged.
func redactOnboardingHistory(history []ChatTurn) []ChatTurn {
	if len(history) == 0 {
		return history
	}

	out := make([]ChatTurn, len(history))
	for i, turn := range history {
		if turn.Role != "assistant" {
			out[i] = turn

			continue
		}

		stage, _, ok := parseOnboardingStageMarker(turn.Content)
		if !ok {
			out[i] = turn

			continue
		}

		placeholder := fmt.Sprintf(
			"(rendered onboarding step %q via the onboarding_step tool; the user has already seen this card. To navigate to another stage, dispatch onboarding_step with the appropriate args — DO NOT reproduce the marker or card text in this turn.)",
			stage,
		)
		out[i] = ChatTurn{Role: turn.Role, Content: placeholder}
	}

	return out
}
