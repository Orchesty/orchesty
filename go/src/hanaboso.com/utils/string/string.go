package string

func Substring(s string, start int, length int) string {

	if start > len(s) {
		return ""
	}

	if length > len(s) {
		length = len(s)
	}

	return s[start:length]
}
