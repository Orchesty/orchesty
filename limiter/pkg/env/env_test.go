package env

import (
	"github.com/stretchr/testify/assert"
	"os"
	"testing"
)

func TestGetEnv(t *testing.T) {
	envVal := "my value"
	os.Setenv("GOENVTEST", envVal)

	assert.Equal(t, envVal, GetEnv("GOENVTEST", ""))
	assert.Equal(t, "default", GetEnv("GOENVTESTNIL", "default"))
}
