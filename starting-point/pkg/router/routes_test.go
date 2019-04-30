package router

import (
	"github.com/stretchr/testify/assert"
	"testing"
)

func TestGetDefaultRoutes(t *testing.T) {
	r := GetDefaultRoutes()

	assert.IsType(t, Routes{}, r)
	assert.Len(t, r, 12)
}
