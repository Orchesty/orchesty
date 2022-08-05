package stringx

import (
	"strings"
)

func ToChar(string, char string) string {
	index := strings.Index(string, char)
	if index < 0 {
		index = len(string)
	}

	return string[:index]
}
