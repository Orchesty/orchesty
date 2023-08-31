package model

import "github.com/hanaboso/pipes/bridge/pkg/enum"

type LogData = map[string]interface{}

type ProcessResult struct {
	message *ProcessMessage
	status  enum.ProcessStatus
	error   error
}

func (p ProcessResult) IsOk() bool {
	return p.status == enum.ProcessStatus_Continue
}

func (p ProcessResult) IsNotError() bool {
	return p.status != enum.ProcessStatus_Error &&
		p.status != enum.ProcessStatus_Trash
}

func (p ProcessResult) Error() error {
	return p.error
}

func (p ProcessResult) Message() *ProcessMessage {
	return p.message
}

func (p ProcessResult) Status() enum.ProcessStatus {
	return p.status
}

func OkResult(message *ProcessMessage) ProcessResult {
	return ProcessResult{
		message: message,
		status:  enum.ProcessStatus_Continue,
		error:   nil,
	}
}

func StopResult(message *ProcessMessage) ProcessResult {
	return ProcessResult{
		message: message,
		status:  enum.ProcessStatus_StopAndOk,
		error:   nil,
	}
}

func PendingResult(message *ProcessMessage) ProcessResult {
	return ProcessResult{
		message: message,
		status:  enum.ProcessStatus_Pending,
		error:   nil,
	}
}

func ErrorResult(message *ProcessMessage, error error) ProcessResult {
	return ProcessResult{
		message: message,
		status:  enum.ProcessStatus_Error,
		error:   error,
	}
}

func TrashResult(message *ProcessMessage, error error) ProcessResult {
	return ProcessResult{
		message: message,
		status:  enum.ProcessStatus_Trash,
		error:   error,
	}
}
