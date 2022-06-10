package enum

import "fmt"

type HeaderType string

const (
	HeaderPrefix                         = "pf-"
	Header_PublishedTimestamp HeaderType = "published-timestamp"
	Header_ProcessStarted     HeaderType = "process-started"
	Header_CorrelationId      HeaderType = "correlation-id"
	Header_TopologyId         HeaderType = "topology-id"
	Header_ProcessId          HeaderType = "process-id"
	Header_ParentProcessId    HeaderType = "parent-id"
)

func PrefixHeader(header string) string {
	return fmt.Sprintf("%s%s", HeaderPrefix, header)
}
