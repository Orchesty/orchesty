package model

import (
	"errors"
	"testing"

	"github.com/stretchr/testify/assert"
)

func TestProcessResult_OkResult(t *testing.T) {
	dto := prepareDto()
	result := OkResult(&dto)
	assert.True(t, result.IsOk())
	assert.Nil(t, result.Error())
}

func TestProcessResult_StopResult(t *testing.T) {
	dto := prepareDto()
	result := StopResult(&dto)
	assert.False(t, result.IsOk())
	assert.Nil(t, result.Error())
}

func TestProcessResult_ErrorResult(t *testing.T) {
	dto := prepareDto()
	err := errors.New("losos")
	result := ErrorResult(&dto, err)
	assert.False(t, result.IsOk())
	assert.Equal(t, err, result.Error())
}
