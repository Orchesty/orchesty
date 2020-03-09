package utils

import (
	"testing"

	"github.com/stretchr/testify/assert"
)

func TestError(t *testing.T) {
	assert.Equal(t, "Error 0: Something gone terribly wrong!", (Error{
		Code:    0,
		Message: "Something gone terribly wrong!",
	}).Error())
}
