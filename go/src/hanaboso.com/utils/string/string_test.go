package string

import (
	"testing"
	"github.com/stretchr/testify/assert"
)

func TestSubstring(t *testing.T) {
	sStr := "s"
	lStr := "Some Long String"

	assert.Equal(t, "s", Substring(sStr, 0, 1))
	assert.Equal(t, "s", Substring(sStr, 0, 4))

	assert.Equal(t, "Some", Substring(lStr, 0, 4))
	assert.Equal(t, "S", Substring(lStr, 0, 1))
	assert.Equal(t, "Some Long String", Substring(lStr, 0, 100))
}
