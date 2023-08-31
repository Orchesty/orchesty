package model

type StatusMessage struct {
	Type     string                 `bson:"type" json:"type"`
	Data     StatusMessageData      `bson:"data" json:"data"`
	Contents []StatusMessageContent `bson:"contents" json:"contents"`
}

type StatusMessageData struct {
	TopologyId    string `bson:"topologyId" json:"topologyId"`
	ResultMessage string `bson:"resultMessage" json:"resultMessage"`
	CorrelationId string `bson:"correlationId" json:"correlationId"`
	ProcessId     string `bson:"processId" json:"processId"`
	User          string `bson:"user" json:"user"`
	TimestampMs   int64  `bson:"timestampMs" json:"timestampMs"`
}

type StatusMessageContent struct {
	TrashId *string `bson:"trashId" json:"trashId"`
	Body    string  `bson:"body" json:"body"`
}
