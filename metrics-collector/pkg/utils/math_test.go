package utils

import "testing"

func TestRoundFloat(t *testing.T) {
	tests := []struct {
		name      string
		value     float64
		precision int
		expected  float64
	}{
		{name: "two decimals", value: 12.349, precision: 2, expected: 12.34},
		{name: "zero precision", value: 12.99, precision: 0, expected: 12},
		{name: "negative value", value: -1.239, precision: 2, expected: -1.23},
		{name: "more precision", value: 1.234567, precision: 4, expected: 1.2345},
	}

	for _, tt := range tests {
		t.Run(tt.name, func(t *testing.T) {
			got := RoundFloat(tt.value, tt.precision)
			if got != tt.expected {
				t.Fatalf("expected %v, got %v", tt.expected, got)
			}
		})
	}
}
