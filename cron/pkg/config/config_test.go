package config

import (
	"os"
	"testing"

	"github.com/stretchr/testify/assert"
)

func TestInit(t *testing.T) {
	load()
	assert.True(t, app.Debug)

	_ = os.Setenv("APP_DEBUG", "false")
	load()
	assert.False(t, app.Debug)
}
