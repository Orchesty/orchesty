package enum

const (
	NodeType_Start   = "start"
	NodeType_Cron    = "cron"
	NodeType_Webhook = "webhook"
)

var NodeType_StartEvents = []string{NodeType_Start, NodeType_Cron, NodeType_Webhook}
