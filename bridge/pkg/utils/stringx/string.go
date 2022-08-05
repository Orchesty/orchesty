package stringx

import (
	"github.com/hanaboso/pipes/bridge/pkg/utils/intx"
	"strings"
)

func ToChar(string, char string) string {
	index := strings.Index(string, char)
	if index < 0 {
		index = len(string)
	}

	return string[:index]
}

func Truncate(str string, max int) string {
	runes := []rune(str)
	maxLen := intx.Min(len(str), max)

	return string(runes[:maxLen])
}
