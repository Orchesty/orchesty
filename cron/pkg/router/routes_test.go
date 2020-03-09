package router

import (
	"testing"

	"github.com/stretchr/testify/assert"
)

func TestRoutes(t *testing.T) {
	assert.Equal(t, 10, len(Routes()))
}
