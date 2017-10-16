export enum PFHeaders {
    // mandatory headers
    CORRELATION_ID = "correlation-id",
    PROCESS_ID = "process-id",
    PARENT_ID = "parent-id",
    SEQUENCE_ID = "sequence-id",
    // topology related headers
    TOPOLOGY_ID = "topology-id",
    TOPOLOGY_NAME = "topology-name",
    // Node label headers
    NODE_ID = "node-id",
    NODE_NAME = "node-name",
    // result headers
    RESULT_CODE = "result-code",
    RESULT_MESSAGE = "result-message",
}
