package service

import (
	"fmt"
	"sort"
	"strings"
)

// BuildSystemPrompt produces the system instructions for the Trace chatbot.
//
// The bot is a navigation assistant over a closed catalogue of audit entities
// fetched from the platform. It is intentionally restricted: it must not invent
// capabilities or attempt to answer general-knowledge questions. The prompt
// teaches the model two reply shapes — an actionable JSON envelope when the
// intent is recognised, or a short conversational JSON envelope otherwise —
// so the backend can deterministically dispatch to /mcp/run or just relay the
// message to the user.
func BuildSystemPrompt(actions []ManifestAction) string {
	var sb strings.Builder

	sb.WriteString("You are the Orchesty Trace assistant. Your sole purpose is to help users find ")
	sb.WriteString("audit logs by mapping their natural-language requests onto a fixed catalogue of ")
	sb.WriteString("audit entities provided below. You do not answer general questions and you do ")
	sb.WriteString("not invent entities, fields or features. Be concise, friendly and proactive: when ")
	sb.WriteString("the user is unclear, suggest the closest matching entity and the attributes you ")
	sb.WriteString("can search by.\n\n")

	if len(actions) == 0 {
		sb.WriteString("AVAILABLE ENTITIES: (none configured yet)\n")
		sb.WriteString("If the user asks for anything, reply that no audit entities are configured ")
		sb.WriteString("and they should add some in the admin UI.\n\n")
	} else {
		sb.WriteString("AVAILABLE ENTITIES:\n")
		for _, action := range actions {
			sb.WriteString(fmt.Sprintf("- %s (id: %q", action.Title, action.ID))
			if action.Kind != "" {
				sb.WriteString(fmt.Sprintf(", kind: %q", action.Kind))
			}
			sb.WriteString(")\n")

			for _, line := range describeProperties(action.InputSchema) {
				sb.WriteString("    " + line + "\n")
			}
		}
		sb.WriteString("\n")
	}

	sb.WriteString("REPLY FORMAT — choose exactly one of the two shapes; output raw JSON only, no ")
	sb.WriteString("markdown fences, no commentary, no extra keys:\n\n")
	sb.WriteString("1. Action — when you can confidently map the request to one entity above and ")
	sb.WriteString("at least one search parameter:\n")
	sb.WriteString("   {\"audit\":\"<entity-id>\",\"data\":{\"<param>\":\"<value>\", ...}}\n")
	sb.WriteString("   Use entity ids and parameter keys EXACTLY as listed (case-sensitive). Only ")
	sb.WriteString("include parameters the user actually provided.\n\n")
	sb.WriteString("2. Reply — for greetings, clarifications, capability questions, or when you ")
	sb.WriteString("cannot map the request:\n")
	sb.WriteString("   {\"reply\":\"<short text for the user>\"}\n")
	sb.WriteString("   Keep it under three sentences. If the user said hi, greet back and explain ")
	sb.WriteString("what you can search. If the request is ambiguous, ask one targeted follow-up ")
	sb.WriteString("question and remind them which entities/attributes you support. Never apologise ")
	sb.WriteString("with a stack-trace, never expose internal field names you did not list above.\n\n")
	sb.WriteString("If the user asks about anything outside this catalogue (the weather, code help, ")
	sb.WriteString("Orchesty configuration, ...), use the Reply shape and politely redirect them to ")
	sb.WriteString("the audit-log search you can perform.")

	return sb.String()
}

// describeProperties renders the JSON-schema properties of a manifest action
// as a list of "key — description (type)" lines, sorted for stable prompts.
func describeProperties(schema map[string]interface{}) []string {
	if schema == nil {
		return nil
	}

	props, ok := schema["properties"].(map[string]interface{})
	if !ok || len(props) == 0 {
		return nil
	}

	keys := make([]string, 0, len(props))
	for k := range props {
		keys = append(keys, k)
	}
	sort.Strings(keys)

	out := make([]string, 0, len(keys))
	for _, k := range keys {
		valMap, _ := props[k].(map[string]interface{})

		desc := ""
		if d, ok := valMap["description"].(string); ok && d != "" {
			desc = " — " + d
		}

		typ := ""
		if t, ok := valMap["type"].(string); ok && t != "" {
			typ = " (" + t + ")"
		}

		out = append(out, fmt.Sprintf("- %s%s%s", k, desc, typ))
	}

	return out
}
