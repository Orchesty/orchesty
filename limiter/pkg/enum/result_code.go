package enum

const (
	ResultCode_Ok                  int = 0
	ResultCode_Repeat              int = 1_001
	ResultCode_ForwardToQueue      int = 1_002
	ResultCode_DoNotContinue       int = 1_003
	ResultCode_LimitExceeded       int = 1_004
	ResultCode_StopAndFail         int = 1_006
	ResultCode_CursorWithFollowers int = 1_010
	ResultCode_CursorOnly          int = 1_011
)
