package stringx

func InArray(haystack []string, needle string) bool {
	for _, value := range haystack {
		if value == needle {
			return true
		}
	}

	return false
}

func IntoInterfaceMap(data map[string]string) map[string]interface{} {
	arr := make(map[string]interface{}, len(data))
	for key, value := range data {
		arr[key] = value
	}

	return arr
}
