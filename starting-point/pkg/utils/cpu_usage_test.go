package utils

import (
	"testing"

	"github.com/stretchr/testify/assert"
)

func TestGetCPUTime(t *testing.T) {
	u, k := GetCPUTime()

	assert.IsType(t, float64(0), u)
	assert.IsType(t, float64(0), k)
}

func TestGetCPUUsage(t *testing.T) {
	u, n := GetCPUUsage(1.0, 5)

	assert.IsType(t, float64(0), u)
	assert.IsType(t, float64(0), n)
}

func TestGetCurrentCPUTimeStat(t *testing.T) {
	u, err := GetCurrentCPUTimeStat()

	assert.NotEmpty(t, u)
	assert.Nil(t, err)
}
