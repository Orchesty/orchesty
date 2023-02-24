package model

import (
	"github.com/stretchr/testify/assert"
	"limiter/pkg/model"
	"testing"
)

func TestAllowedBatch(t *testing.T) {
	tests := []struct {
		max     int
		time    int
		allowed int
		result  int
	}{
		{5, 5, 5, 1},
		{50, 1, 2, 2},
		{50, 1, 50, 6},
		{1_000, 5, 50, 23},
	}

	for _, test := range tests {
		limit := model.Limit{
			Time:    test.time,
			Maximum: test.max,
			Allowed: test.allowed,
		}
		if test.allowed > test.result {
			assert.GreaterOrEqual(t, test.result*9*test.time, test.max)
		}
		assert.Equal(t, test.result, limit.AllowedBatch())
	}
}
