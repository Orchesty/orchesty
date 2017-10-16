export enum PFHeaders {
    // mandatory headers
    CORRELATION_ID = "correlation_id",
    PROCESS_ID = "process_id",
    PARENT_ID = "parent_id",
    SEQUENCE_ID = "sequence_id",
    // topology related headers
    TOPOLOGY_ID = "topology_id",
    TOPOLOGY_NAME = "topology_name",
    // Node label headers
    NODE_ID = "node_id",
    NODE_NAME = "node_name",
    // result headers
    RESULT_CODE = "result_code",
    RESULT_MESSAGE = "result_message",
}
