package enum

type Header string

const (
	Header_CorrelationId Header = "correlation-id"
	Header_ProcessId     Header = "process-id"
	// TODO Force which nodes should receive message (currently supports 1, should be an array)
	Header_ForceTargetQueue Header = "force-target-queue"
	Header_NodeId           Header = "node-id"
	Header_NodeName         Header = "node-name"
	Header_ResultCode       Header = "result-code"
	Header_ResultMessage    Header = "result-message"
	Header_TopologyId       Header = "topology-id"
	Header_TopologyName     Header = "topology-name"
	// List of following nodes
	Header_WorkerFollowers Header = "worker-followers"

	// Target queue from repeater
	Header_RepeatQueue Header = "repeat-queue"
	// Repeat delay
	Header_RepeatInterval Header = "repeat-queue"
	// Max retries
	Header_RepeatMaxHops Header = ""
	// Current retry
	Header_RepeatHops Header = ""

	// Format: key;group;time;amount;key2;group2;time;amount
	Header_LimitKey Header = "limiter-key"
	// Routing headers for limiter service
	Header_LimitReturnExchange   Header = "limit-return-exchange"
	Header_LimitReturnRoutingKey Header = "limit-return-routing-key"
	// Message came from limiter (bool)
	Header_LimitMessageFromLimiter Header = "limit-message-from-limiter"
)
