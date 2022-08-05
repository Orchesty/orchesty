package worker

import (
	"github.com/hanaboso/pipes/bridge/pkg/enum"
	"github.com/stretchr/testify/assert"
	"strconv"
	"testing"
)

func TestBatch_AfterProcess(t *testing.T) {
	worker := Batch{}
	dto := prepDto()
	dto.Body = []byte("[{\"body\":\"a\",\"headers\":null}]")
	_, p := worker.AfterProcess(nullNode{}, dto)

	assert.Equal(t, 3, p)
}

func TestBatch_AfterProcessCursor(t *testing.T) {
	worker := Batch{}
	dto := prepDto()
	dto.Body = []byte("[{\"body\":\"a\",\"headers\":null}]")
	dto.SetHeader(enum.Header_ResultCode, strconv.Itoa(enum.ResultCode_CursorWithFollowers))
	_, p := worker.AfterProcess(nullNode{}, dto)

	assert.Equal(t, 4, p)
}

func TestBatch_AfterProcessCursorOnly(t *testing.T) {
	worker := Batch{}
	dto := prepDto()
	dto.Body = []byte("[{\"body\":\"a\",\"headers\":null}]")
	dto.SetHeader(enum.Header_ResultCode, strconv.Itoa(enum.ResultCode_CursorOnly))
	_, p := worker.AfterProcess(nullNode{}, dto)

	assert.Equal(t, 1, p)
}
