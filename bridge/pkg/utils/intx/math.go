package intx

func Max(a, b int) int {
	if a >= b {
		return a
	}

	return b
}

func Min(a, b int) int {
	if a <= b {
		return a
	}

	return b
}

func IntDefault(value, def int) int {
	if value == 0 {
		return def
	}

	return value
}
