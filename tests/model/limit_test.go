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

func TestParseLimits(t *testing.T) {
	limits := model.ParseLimits("orchesty|SYS1;1;60;orchesty|SYS2;1;30")

	limit1 := model.Limit{
		FullKey:       "orchesty|SYS1",
		SystemKey:     "SYS1",
		UserKey:       "orchesty",
		Time:          1,
		TimeToRefresh: 1,
		Maximum:       60,
		Allowed:       60,
		Running:       0,
		Empty:         0,
	}
	assert.Equal(t, limit1, limits[0])

	limit2 := model.Limit{
		FullKey:       "orchesty|SYS2",
		SystemKey:     "SYS2",
		UserKey:       "orchesty",
		Time:          1,
		TimeToRefresh: 1,
		Maximum:       30,
		Allowed:       30,
		Running:       0,
		Empty:         0,
	}
	assert.Equal(t, limit2, limits[1])
}
