package router

import (
	"testing"

	"github.com/stretchr/testify/assert"
)

func TestGetDefaultRoutes(t *testing.T) {
	r := GetDefaultRoutes()

	assert.IsType(t, Routes{}, r)
	assert.Len(t, r, 15)
}
