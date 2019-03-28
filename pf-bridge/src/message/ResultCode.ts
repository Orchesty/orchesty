// Add new result codes as you wish, but always keep 0 for success
// and keep these codes synchronized with workers implementations

export enum ResultCodeGroup {
    SUCCESS = 0,
    NON_STANDARD = 1,
    MESSAGE_ERROR = 2,
    BRIDGE_ERROR = 3,
    WORKER_ERROR = 4,
}

// Always sync this enum with following page:
// https://hanaboso.atlassian.net/wiki/spaces/PIP/pages/105119850/Bridge-Worker+komunikace

export enum ResultCode {
    // OK
    SUCCESS = 0,

    // NON_STANDARD: 1000+
    REPEAT = 1001,
    FORWARD_TO_TARGET_QUEUE = 1002,
    DO_NOT_CONTINUE = 1003,
    LIMIT_EXCEEDED = 1004,
    SPLITTER_BATCH_END = 1005,
    STOP_AND_FAILED = 1006,

    // MESSAGE ERRORS: 2000+
    UNKNOWN_ERROR = 2001,
    INVALID_HEADERS = 2002,
    INVALID_CONTENT = 2003,

    // BRIDGE_ERRORS: 3000+
    BRIDGE_TIMEOUT = 3001,
    MISSING_RESULT_CODE = 3002,
    MESSAGE_ALREADY_BEING_PROCESSED = 3003,
    MESSAGE_NOT_PROCESSED = 3004,
    HTTP_ERROR = 3005,
    AMQPRPC_INVALID_CORRELATION_ID = 3006,
    AMQPRPC_INVALID_MESSAGE_TYPE = 3007,
    INVALID_NON_STANDARD_CODE = 3008,
    INVALID_NON_STANDARD_TARGET_QUEUE = 3009,
    REPEAT_INVALID_QUEUE = 3010,
    REPEAT_INVALID_HOPS = 3011,
    REPEAT_MAX_HOPS_REACHED = 3012,
    REPEAT_INVALID_INTERVAL = 3013,
    CHILD_PROCESS_ERROR = 3014,

    // WORKER_ERRORS: 4000+
    WORKER_TIMEOUT = 4001,
}
