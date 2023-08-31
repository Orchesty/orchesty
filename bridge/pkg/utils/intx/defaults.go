package intx

func Default(value, def int) int {
	if value == 0 {
		return def
	}

	return value
}
