package model

type StatusMessage struct {
	Type     string                 `bson:"type"`
	Data     StatusMessageData      `bson:"data"`
	Contents []StatusMessageContent `bson:"contents"`
}

type StatusMessageData struct {
	TopologyId    string `bson:"topologyId"`
	ResultMessage string `bson:"resultMessage"`
	CorrelationId string `bson:"correlationId"`
	ProcessId     string `bson:"processId"`
	User          string `bson:"user"`
	TimestampMs   int64  `bson:"timestampMs"`
}

type StatusMessageContent struct {
	TrashId *string `bson:"trashId"`
	Body    string  `bson:"body"`
}
