package enum

type ResultCode int

const (
    ResultCode_Ok             ResultCode = 0
    ResultCode_Repeat         ResultCode = 1_001
    ResultCode_ForwardToQueue ResultCode = 1_002
    ResultCode_DoNotContinue  ResultCode = 1_003
    ResultCode_LimitExceeded  ResultCode = 1_004
    ResultCode_StopAndFail    ResultCode = 1_006
)
