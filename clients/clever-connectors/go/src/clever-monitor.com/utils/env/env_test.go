package env

import (
	"testing"
	"os"
	"github.com/stretchr/testify/assert"
)

func TestGetEnv(t *testing.T) {
	envVal := "my value"
	os.Setenv("GOENVTEST", envVal)

	assert.Equal(t, envVal, GetEnv("GOENVTEST", ""))
	assert.Equal(t, "default", GetEnv("GOENVTESTNIL", "default"))
}
