package enum

type ProcessStatus int

const (
    ProcessStatus_Continue  ProcessStatus = 0
    ProcessStatus_StopAndOk ProcessStatus = 1
    ProcessStatus_Error     ProcessStatus = 2
)
