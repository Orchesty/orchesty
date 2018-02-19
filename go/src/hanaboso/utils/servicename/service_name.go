package servicename

import "strings"
import str "hanaboso/utils/strings"

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
			result = append(result, str.Substring(item, 0, 3))
		}
	}

	return strings.ToLower(str.Substring(strings.Join(result, "-"), 0, 64))
}
