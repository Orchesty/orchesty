package model

import (
	"errors"
	"github.com/stretchr/testify/assert"
	"testing"
)

func TestProcessResult_OkResult(t *testing.T) {
	dto := prepareDto()
	result := OkResult(&dto)
	assert.True(t, result.IsOk())
	assert.False(t, result.IsError())
	assert.Nil(t, result.Error())
}

func TestProcessResult_StopResult(t *testing.T) {
	dto := prepareDto()
	result := StopResult(&dto)
	assert.False(t, result.IsOk())
	assert.False(t, result.IsError())
	assert.Nil(t, result.Error())
}

func TestProcessResult_ErrorResult(t *testing.T) {
	dto := prepareDto()
	err := errors.New("losos")
	result := ErrorResult(&dto, err)
	assert.False(t, result.IsOk())
	assert.True(t, result.IsError())
	assert.Equal(t, err, result.Error())
}
