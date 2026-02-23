package utils

// RoundFloat rounds a float64 value to the specified precision
func RoundFloat(val float64, precision int) float64 {
	ratio := 1.0
	for i := 0; i < precision; i++ {
		ratio *= 10
	}
	return float64(int64(val*ratio)) / ratio
}
