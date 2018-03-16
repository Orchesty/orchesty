package strings

import (
	"math/rand"
	"time"
)

var r *rand.Rand

func init() {
	r = rand.New(rand.NewSource(time.Now().UnixNano()))
}

func Substring(s string, start int, length int) string {

	if start > len(s) {
		return ""
	}

	if length > len(s) {
		length = len(s)
	}

	return s[start:length]
}

func Random(strlen int, allowDigits bool) string {
	chars := "abcdefghijklmnopqrstuvwxyz"
	if allowDigits {
		chars = chars + "0123456789"
	}
	result := make([]byte, strlen)
	for i := range result {
		result[i] = chars[r.Intn(len(chars))]
	}
	return string(result)
}
