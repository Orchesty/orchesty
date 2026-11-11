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
	Header_NodeName         = "node-name"
	Header_SequenceId       = "sequence-id"
	Header_ResultCode       = "result-code"
	Header_ResultMessage    = "result-message"
	// Debug detail
	Header_ResultDetail = "result-detail"
	Header_TopologyId   = "topology-id"
	// TODO deprecated totok by mělo být vyřešeno novým gateway workerem
	Header_WorkerFollowers = "worker-followers"
	Header_SystemEvent     = "system-event"
	Header_Source          = "source"

	Source_UI    = "ui"
	Source_Auto  = "auto"
	Source_Pulse = "pulse"

	Header_ConfirmQuestion = "confirm-question"
	Header_ConfirmDisplay  = "confirm-display"
	Header_PulseEvent      = "pulse-event"
	// Set by the backend only when a live Confirm / Question task is decided (accept / reject). A plain
	// user-task-state without it is a Trash retry / reject, which must not count as the user's decision.
	Header_PulseDecision = "pulse-decision"

	Header_QuestionText   = "question-text"
	Header_QuestionSchema = "question-schema"
	Header_QuestionSkip   = "question-skip"

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
	Header_Application             = "application"
	Header_Applications            = "applications"
	Header_Sdk                     = "sdk"

	// enum.UserTask (pending, failed) marker for counter & if message came from db
	Header_UserTaskState = "user-task-state"

	// Publish time for metrics
	// !!! This is single header still inside rabbitMq headers and not in body !!!
	Header_PublishedTimestamp = "published-timestamp"
	Header_ProcessedTimestamp = "processed-timestamp"

	// Batch
	Header_Cursor = "cursor"

	// Audit
	Header_AuditEntityHeader       = "audit-entity"
	Header_AuditEntityIdsHeader    = "audit-entity-ids"
	Header_AuditEntityFieldsHeader = "audit-entity-fields"
	Header_AuditCheckpoint         = "audit-checkpoint"

	// Reply headers — injected at the topology start (e.g. by Pulse) and
	// propagated through the whole topology so a terminal response node can POST
	// the final payload back to the caller. ReplyTo is the callback URL,
	// ReplyId correlates the response with the original request.
	Header_ReplyTo = "reply-to"
	Header_ReplyId = "reply-id"

	// Identity of the real caller behind a run (e.g. the authenticated user
	// behind a Pulse run). The `user` header stays the system identity
	// ('orchesty') for shared ApplicationInstall lookups, so the caller's email
	// travels here instead. Part of IdentityHeaders so no worker can overwrite
	// it and it survives every hop.
	Header_Identity = "identity"
)

var IdentityHeaders = []string{
	Header_CorrelationId,
	Header_ProcessId,
	Header_ParentProcessId,
	Header_PreviousNodeId,
	Header_NodeId,
	Header_NodeName,
	Header_TopologyId,
	Header_SequenceId,
	Header_User,
	Header_WorkerFollowers,
	Header_SystemEvent,
	// Reply headers belong to the whole process and must survive every worker
	// hop (FromHttpResponse otherwise drops anything the SDK does not echo back)
	// so the terminal response node can always reach the caller's callback.
	Header_ReplyTo,
	Header_ReplyId,
	// Caller identity (e.g. the authenticated user behind a Pulse run) must
	// survive every hop and never be overwritten by a worker, so the topology
	// can act on the real user's behalf all the way to the response node.
	Header_Identity,
	Header_Source,
}
