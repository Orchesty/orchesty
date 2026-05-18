package sanitize

import (
	"strings"

	"golang.org/x/text/unicode/norm"
)

// StripDiacritics removes combining diacritical marks from the input text.
func StripDiacritics(value string) string {
	value = strings.TrimSpace(value)
	value = strings.ToLower(value)

	return norm.NFD.String(value)
}
