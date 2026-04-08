package service

import (
	"fmt"
	"strings"
)

func BuildPrompt(userContent string, actions []ManifestAction) string {
	var sb strings.Builder

	sb.WriteString("You are an assistant that helps users interact with the system.\n")
	sb.WriteString(fmt.Sprintf("The user sent the following message:\n\"%s\"\n\n", userContent))
	sb.WriteString("These are the available actions:\n")

	for i, action := range actions {
		sb.WriteString(fmt.Sprintf("%d. \"%s\" (id: \"%s\", kind: \"%s\")\n", i+1, action.Title, action.ID, action.Kind))

		props, ok := action.InputSchema["properties"]
		if !ok {
			continue
		}

		propsMap, ok := props.(map[string]interface{})
		if !ok {
			continue
		}

		sb.WriteString("   Parameters:\n")
		for key, val := range propsMap {
			desc := ""
			if valMap, ok := val.(map[string]interface{}); ok {
				if d, ok := valMap["description"]; ok {
					desc = fmt.Sprintf(" - %v", d)
				}
			}
			sb.WriteString(fmt.Sprintf("   - %s%s\n", key, desc))
		}
	}

	sb.WriteString("\nBased on the user's message, determine which action they want to perform and with what parameters.\n")
	sb.WriteString("Respond with raw JSON only. Do not wrap it in markdown code fences or any other formatting.\n")
	sb.WriteString("Use the exact action id value as provided — it is case-sensitive.\n")
	sb.WriteString("{\"audit\": \"<action-id>\", \"data\": {\"<param>\": \"<value>\"}}\n")
	sb.WriteString("If you cannot determine the action, respond with:\n")
	sb.WriteString("{\"error\": \"I could not understand your request.\"}\n")

	return sb.String()
}
