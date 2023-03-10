package model

import (
	"testing"

	"github.com/stretchr/testify/assert"
)

func TestProcessDto_GetHeader(t *testing.T) {
	dto := prepareDto()
	value, err := dto.GetHeader("string")
	assert.Nil(t, err)
	assert.Equal(t, "string", value)

	value, err = dto.GetHeader("losos")
	assert.NotNil(t, err)
	assert.Equal(t, "", value)
}

func TestProcessDto_GetHeaderOrDefault(t *testing.T) {
	dto := prepareDto()
	value := dto.GetHeaderOrDefault("string", "asd")
	assert.Equal(t, "string", value)

	value = dto.GetHeaderOrDefault("losos", "asd")
	assert.Equal(t, "asd", value)
}

func TestProcessDto_GetIntHeader(t *testing.T) {
	dto := prepareDto()
	value, err := dto.GetIntHeader("int")
	assert.Nil(t, err)
	assert.Equal(t, 666, value)

	value, err = dto.GetIntHeader("losos")
	assert.NotNil(t, err)
	assert.Equal(t, 0, value)
}

func TestProcessDto_GetIntHeaderOrDefault(t *testing.T) {
	dto := prepareDto()
	value := dto.GetIntHeaderOrDefault("int", 5)
	assert.Equal(t, 666, value)

	value = dto.GetIntHeaderOrDefault("losos", 5)
	assert.Equal(t, 5, value)
}

func TestProcessDto_SetHeader(t *testing.T) {
	dto := prepareDto()
	dto.SetHeader("a", "a")

	value, err := dto.GetHeader("a")
	assert.Nil(t, err)
	assert.Equal(t, "a", value)
}

func prepareDto() ProcessMessage {
	dto := ProcessMessage{}
	dto.SetHeader("string", "string")
	dto.SetHeader("int", "666")

	return dto
}
