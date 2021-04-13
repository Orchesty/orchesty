package model

import "github.com/hanaboso/pipes/bridge/pkg/enum"

type ProcessResult struct {
	message *ProcessDto
	status  enum.ProcessStatus
	error   error
}

func (p ProcessResult) IsOk() bool {
	return p.status == enum.ProcessStatus_Continue
}

func (p ProcessResult) IsError() bool {
	return p.status == enum.ProcessStatus_Error
}

func (p ProcessResult) Error() error {
	return p.error
}

func (p ProcessResult) Message() *ProcessDto {
	return p.message
}

func OkResult(message *ProcessDto) ProcessResult {
	return ProcessResult{
		message: message,
		status:  enum.ProcessStatus_Continue,
		error:   nil,
	}
}

func StopResult(message *ProcessDto) ProcessResult {
	return ProcessResult{
		message: message,
		status:  enum.ProcessStatus_StopAndOk,
		error:   nil,
	}
}

func ErrorResult(message *ProcessDto, error error) ProcessResult {
	return ProcessResult{
		message: message,
		status:  enum.ProcessStatus_Error,
		error:   error,
	}
}
