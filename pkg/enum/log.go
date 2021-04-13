package enum

/*
Example format:
{
    "timestamp": 1510236541,
    "level": "ERROR",
    "service": "bridge",
    "message": "whops something went wrong",

    "trace": [{"file":"", "function": ""}],
    "topologyId": "guid",
    "nodeId": "guid",
    "correlationId": "guid",
    "processId": "guid",

    "data": {
        "any": "",
        "optional": "",
        "field": ""
    }
}
*/

const (
	LogHeader_NodeId        = "nodeId"
	LogHeader_TopologyId    = "topologyId"
	LogHeader_Data          = "data"
	LogHeader_Service       = "service"
	LogHeader_ProcessId     = "processId"
	LogHeader_CorrelationId = "correlationId"
	LogHeader_Trace         = "trace"
	LogHeader_Timestamp     = "timestamp"
	LogHeader_Message       = "message"
)
