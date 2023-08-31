package enum

type HeaderType string

const (
	Header_PublishedTimestamp HeaderType = "published-timestamp"

	Header_ProcessStarted  HeaderType = "process-started"
	Header_CorrelationId   HeaderType = "correlation-id"
	Header_TopologyId      HeaderType = "topology-id"
	Header_ProcessId       HeaderType = "process-id"
	Header_ParentProcessId HeaderType = "parent-id"
	Header_User            HeaderType = "user"
)
