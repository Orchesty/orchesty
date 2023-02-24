package enum

const (
	Header_CorrelationId   = "correlation-id"
	Header_ProcessId       = "process-id"
	Header_ParentProcessId = "parent-id"
	Header_PreviousNodeId  = "previous-node-id"
	Header_User            = "user"
	// TODO deprecated totok by mělo být vyřešeno novým gateway workerem
	Header_ForceTargetQueue = "force-target-queue"
	Header_NodeId           = "node-id"
	Header_ResultCode       = "result-code"
	Header_ResultMessage    = "result-message"
	// Debug detail
	Header_ResultDetail = "result-detail"
	Header_TopologyId   = "topology-id"
	// TODO deprecated totok by mělo být vyřešeno novým gateway workerem
	Header_WorkerFollowers = "worker-followers"
	Header_SystemEvent     = "system-event"
	Header_Application     = "application"

	// Target queue from repeater
	Header_RepeatQueue = "repeat-queue"
	// Repeat delay
	Header_RepeatInterval = "repeat-interval"
	// Max retries
	Header_RepeatMaxHops = "repeat-max-hops"
	// Current retry
	Header_RepeatHops = "repeat-hops"

	// Format: key;group;time;amount;key2;group2;time;amount
	Header_LimitKey     = "limiter-key"
	Header_LimitKeyBase = "limiter-key-base"
	// Routing headers for limiter service
	Header_LimitReturnExchange   = "limit-return-exchange"
	Header_LimitReturnRoutingKey = "limit-return-routing-key"
	// Message came from limiter (bool)
	Header_LimitMessageFromLimiter = "limit-message-from-limiter"

	// enum.UserTask (pending, failed) marker for counter & if message came from db
	Header_UserTaskState = "user-task-state"

	// Publish time for metrics
	// !!! This is single header still inside rabbitMq headers and not in body !!!
	Header_PublishedTimestamp = "published-timestamp"

	// Batch
	Header_Cursor = "cursor"
)
