package strings

import (
	"github.com/stretchr/testify/assert"
	"strings"
	"testing"
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

func TestRandom(t *testing.T) {
	assert.Len(t, Random(10, true), 10)
	assert.Len(t, Random(10, false), 10)

	assert.True(t, strings.ContainsAny(Random(1000, true), "0123456789"), "Generated string should contain some digits")
	assert.False(t, strings.ContainsAny(Random(1000, false), "0123456789"), "Generated string should not contain digits")
}
