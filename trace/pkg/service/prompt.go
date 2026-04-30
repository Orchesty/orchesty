package service

import (
	"fmt"
	"sort"
	"strings"
)

// BuildSystemPrompt produces the system instructions for the Trace chatbot.
//
// The bot is a router over a fixed manifest of MCP actions: per-entity audit
// histories ("entity_history" kind) plus a small set of metrics tools
// ("timeseries", "list", ...). It is intentionally restricted: it must not
// invent capabilities or attempt to answer general-knowledge questions. The
// prompt teaches the model three reply shapes — an entity-history envelope
// (audit + data + optional date), a generic tool envelope (tool + args), or
// a short conversational reply — so the backend can deterministically
// dispatch to /mcp/run or just relay the message to the user.
func BuildSystemPrompt(actions []ManifestAction) string {
	var sb strings.Builder

	entityActions, toolActions := splitActionsByKind(actions)
	hasDocsSearch := containsActionID(toolActions, "docs_search")

	sb.WriteString("You are the Orchesty Trace assistant. Your sole purpose is to help users navigate ")
	sb.WriteString("the platform's audit logs and process metrics by mapping their natural-language ")
	sb.WriteString("requests onto a fixed catalogue of MCP actions provided below. You do not answer ")
	sb.WriteString("general questions and you do not invent entities, fields or features. Be concise, ")
	sb.WriteString("friendly and proactive: when the user is unclear, suggest the closest matching ")
	sb.WriteString("action and the attributes you can search by.\n\n")

	if len(entityActions) == 0 {
		sb.WriteString("AVAILABLE ENTITIES: (none configured yet)\n")
		sb.WriteString("If the user asks for entity-specific history, reply that no audit entities ")
		sb.WriteString("are configured and they should add some in the admin UI.\n\n")
	} else {
		sb.WriteString("AVAILABLE ENTITIES (use with the {audit, data} envelope):\n")
		for _, action := range entityActions {
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

	if len(toolActions) > 0 {
		sb.WriteString("AVAILABLE TOOLS (use with the {tool, args} envelope):\n")
		for _, action := range toolActions {
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

	sb.WriteString("DATE RANGES — most actions accept an optional date window. Pick exactly one of:\n")
	sb.WriteString("- \"day\": \"YYYY-MM-DD\"   — single calendar day (UTC)\n")
	sb.WriteString("- \"from\" + \"to\":          — explicit ISO 8601 range, both required together\n")
	sb.WriteString("- \"period\": one of today | yesterday | this_week | last_7d | last_30d\n")
	sb.WriteString("Never pass more than one of these in a single request.\n\n")

	sb.WriteString("REPLY FORMAT — choose exactly one of the three shapes; output raw JSON only, ")
	sb.WriteString("no markdown fences, no commentary, no extra keys:\n\n")
	sb.WriteString("1. Entity history — when the user asks about a specific entity and provides at ")
	sb.WriteString("least one identifier:\n")
	sb.WriteString("   {\"audit\":\"<entity-id>\",\"data\":{\"<param>\":\"<value>\", ...}}\n")
	sb.WriteString("   Optional date filter goes on the TOP LEVEL alongside `audit` / `data`, NOT ")
	sb.WriteString("inside `data`:\n")
	sb.WriteString("   {\"audit\":\"product\",\"data\":{\"SKU\":\"sku-055\"},\"day\":\"2026-03-12\"}\n")
	sb.WriteString("   Use entity ids and parameter keys EXACTLY as listed (case-sensitive). Only ")
	sb.WriteString("include parameters the user actually provided.\n\n")
	sb.WriteString("2. Tool — when the user asks a metrics question that one of the tools above ")
	sb.WriteString("answers (process counts, failing connectors, ...):\n")
	sb.WriteString("   {\"tool\":\"<tool-id>\",\"args\":{\"<arg>\":\"<value>\", ...}}\n")
	sb.WriteString("   Examples:\n")
	sb.WriteString("   - \"how many processes ran last week\" → ")
	sb.WriteString("{\"tool\":\"processes_timeseries\",\"args\":{\"period\":\"last_7d\"}}\n")
	sb.WriteString("   - \"which topologies were running today\" → ")
	sb.WriteString("{\"tool\":\"topologies_activity\",\"args\":{\"period\":\"today\"}}\n")
	sb.WriteString("   - \"jaké topologie běžely tento týden\" → ")
	sb.WriteString("{\"tool\":\"topologies_activity\",\"args\":{\"period\":\"this_week\"}}\n")
	sb.WriteString("   - \"which connector fails most today\" → ")
	sb.WriteString("{\"tool\":\"failing_connectors\",\"args\":{\"period\":\"today\"}}\n")
	sb.WriteString("   - \"process counts on 2026-03-12\" → ")
	sb.WriteString("{\"tool\":\"processes_timeseries\",\"args\":{\"day\":\"2026-03-12\"}}\n")
	sb.WriteString("   - \"show me the last errors\" → ")
	sb.WriteString("{\"tool\":\"recent_errors\",\"args\":{\"period\":\"last_7d\"}}\n")
	sb.WriteString("   Pick processes_timeseries when the user asks about MESSAGE volumes over time ")
	sb.WriteString("(\"how many processes\", \"failure rate\"); pick topologies_activity when the user ")
	sb.WriteString("asks WHICH topologies were active (\"which topologies\", \"what was running\", ")
	sb.WriteString("\"jaké topologie\").\n")
	if hasDocsSearch {
		sb.WriteString("   - \"how do I get started\" → ")
		sb.WriteString("{\"tool\":\"docs_search\",\"args\":{\"query\":\"how do I get started\",\"locale\":\"en\"}}\n")
		sb.WriteString("   - \"jak nastavím OAuth2 aplikaci\" → ")
		sb.WriteString("{\"tool\":\"docs_search\",\"args\":{\"query\":\"jak nastavím OAuth2 aplikaci\",\"locale\":\"cs\"}}\n")
		sb.WriteString("   - \"what is a topology\" → ")
		sb.WriteString("{\"tool\":\"docs_search\",\"args\":{\"query\":\"what is a topology\",\"locale\":\"en\"}}\n")
		sb.WriteString("   For docs_search ALWAYS forward the user's wording verbatim as `query` ")
		sb.WriteString("and pick `locale` from the language they wrote in. Use it for any ")
		sb.WriteString("\"how do I…\" / \"what is…\" / \"jak…\" platform-usage question.\n")
	}
	sb.WriteString("   Do NOT use the tool envelope for entity history — those go through shape 1.\n\n")
	sb.WriteString("3. Reply — for greetings, clarifications, capability questions, or when you ")
	sb.WriteString("cannot map the request:\n")
	sb.WriteString("   {\"reply\":\"<short text for the user>\"}\n")
	sb.WriteString("   Keep it under three sentences. If the user said hi, greet back and explain ")
	sb.WriteString("what you can search. If the request is ambiguous, ask one targeted follow-up ")
	sb.WriteString("question and remind them which actions/attributes you support. Never apologise ")
	sb.WriteString("with a stack-trace, never expose internal field names you did not list above.\n\n")
	if hasDocsSearch {
		sb.WriteString("For platform-usage / how-to questions, ALWAYS prefer the docs_search tool over ")
		sb.WriteString("the Reply shape. Only fall back to Reply when the question is genuinely off-topic ")
		sb.WriteString("(weather, unrelated code help, etc.).\n")
	}
	sb.WriteString("If the user asks about anything outside this catalogue (the weather, code help, ")
	sb.WriteString("Orchesty configuration, ...), use the Reply shape and politely redirect them to ")
	sb.WriteString("the audit-log searches and metrics tools you can perform.")

	return sb.String()
}

// containsActionID reports whether the given action list contains an action
// with the matching id. Used to gate prompt sections that only make sense
// when a particular optional tool is wired in.
func containsActionID(actions []ManifestAction, id string) bool {
	for _, a := range actions {
		if a.ID == id {
			return true
		}
	}

	return false
}

// BuildSummariserPrompt instructs the model to turn a compact JSON tool result
// into short user-facing prose. It runs as a SECOND LLM pass after the tool
// call returns: the user message in this pass is the raw JSON envelope, and
// the model must rewrite it without inventing fields. Keeping this prompt
// focused (no chat history, no manifest) makes the second pass cheap and
// avoids confusing the model with the original request.
func BuildSummariserPrompt(toolID string) string {
	var sb strings.Builder

	sb.WriteString("You are summarising the JSON result of an Orchesty MCP tool call ")
	if toolID != "" {
		sb.WriteString(fmt.Sprintf("(tool id: %q)", toolID))
	} else {
		sb.WriteString("(tool id unknown)")
	}
	sb.WriteString(" for an end user.\n\n")
	sb.WriteString("RULES:\n")
	sb.WriteString("- Reply in plain text only. NO markdown fences, NO JSON, NO code blocks.\n")
	sb.WriteString("- For a list/items result: produce a short bullet list (max ~10 items) ")
	sb.WriteString("naming each item in human terms (use display names like nodeName / topologyName ")
	sb.WriteString("when present, never raw IDs).\n")
	sb.WriteString("- For a timeseries/points result: write 1–3 short sentences with totals ")
	sb.WriteString("(success, failed, total, period) and a one-line trend if obvious.\n")
	sb.WriteString("- Do NOT invent fields that are not present in the JSON.\n")
	sb.WriteString("- If the result is empty (no items / no points / total = 0), say so explicitly ")
	sb.WriteString("in one sentence.\n")
	sb.WriteString("- Keep the answer under 6 short sentences.\n")

	if toolID == "docs_search" {
		sb.WriteString("\nDOCS_SEARCH SPECIFICS:\n")
		sb.WriteString("- The JSON has shape {results: [{path, title, description, snippet, source}], latestVersion}.\n")
		sb.WriteString("- For each result, write one line: \"<title> — <one-sentence excerpt taken or paraphrased ")
		sb.WriteString("from snippet>\" followed by the URL \"https://orchesty.io<path>\".\n")
		sb.WriteString("- Order results from most relevant (first in the array) to least.\n")
		sb.WriteString("- Reply in the same language the user wrote in. If the user wrote in Czech, ")
		sb.WriteString("translate the excerpt into natural Czech; the URL stays as-is.\n")
		sb.WriteString("- If results is empty, say in one sentence that no documentation matched and ")
		sb.WriteString("suggest rephrasing the question. Do not apologise.\n")
		sb.WriteString("- Never invent paths, titles or URLs. Quote them only when they appear in `results`.\n")
	}

	return sb.String()
}

// splitActionsByKind separates entity_history actions (per-entity audit) from
// the generic tool actions (timeseries, list, ...). Stable ordering matches
// the manifest order so prompts stay deterministic across runs.
func splitActionsByKind(actions []ManifestAction) (entities, tools []ManifestAction) {
	for _, action := range actions {
		switch action.Kind {
		case "", "entity_history", "query":
			entities = append(entities, action)
		default:
			tools = append(tools, action)
		}
	}

	return entities, tools
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
