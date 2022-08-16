package stringx

import "strings"

func RemovePostfix(str, delimeter string) string {
	index := strings.LastIndex(str, delimeter)
	if index <= 0 {
		return str
	}

	return str[:index]
}
