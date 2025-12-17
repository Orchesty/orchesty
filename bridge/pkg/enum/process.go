package enum

import "fmt"

type ProcessStatus int
type MessageStatus int

const (
	ProcessStatus_Continue ProcessStatus = iota + 1
	ProcessStatus_StopAndOk
	ProcessStatus_Pending

	ProcessStatus_Error
	ProcessStatus_Trash
)

const (
	MessageStatus_Received MessageStatus = iota + 1
	MessageStatus_Consumed
	MessageStatus_LimiterCheck
	MessageStatus_BeforeProcess
	MessageStatus_CheckResultCode
	MessageStatus_AfterProcess
	MessageStatus_InnerProcessDone
	MessageStatus_Counter
	MessageStatus_Trash
	MessageStatus_Metrics
	MessageStatus_Ack
	MessageStatus_Failed
	MessageStatus_Fatal
	MessageStatus_Done
)

var (
	messageStatus_Values = [...]string{"Uninitialized", "Received", "Consumed", "Limiter", "BeforeProcess", "ResultCheck", "AfterProcess", "InnerDone", "Counter", "Trash", "Metrics", "Ack", "Failed", "Fatal", "Done"}
)

func (status MessageStatus) String() string {
	if int(status) < len(messageStatus_Values[status]) {
		return messageStatus_Values[status]
	}

	return fmt.Sprintf("Unnamed: %d", status)
}
