package enum

type UserTaskState bool

const (
	// UI actions
	UserTask_Accept = "accept"
	UserTask_Reject = "reject"

	// Node setting used for multi-counter
	UserTaskSetting_Pending = "pending"
	UserTaskSetting_Stop    = "stop"

	UserTaskState_Wait   UserTaskState = true
	UserTaskState_NoWait UserTaskState = false
)
