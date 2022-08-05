package model

import "strings"

// Substring Substring
func Substring(s string, start int, length int) string {
	if start > len(s) {
		return ""
	}

	if length > len(s) {
		length = len(s)
	} else if length == 0 {
		length = len(s)
	}

	if length < start {
		if length+start > len(s) {
			length = len(s)
		} else {
			length = length + start
		}
	} else {
		if len(s)-(start+length) < 0 {
			length = len(s)
		} else {
			length = start + length
		}
	}

	return s[start:length]
}

// CreateServiceName CreateServiceName
func CreateServiceName(s string) string {
	var (
		item   string
		i      int
		result []string
	)

	for i, item = range strings.Split(s, "-") {
		if i == 0 {
			result = append(result, item)
		} else {
			result = append(result, Substring(item, 0, 3))
		}
	}

	return strings.ToLower(Substring(strings.Join(result, "-"), 0, 64))
}
