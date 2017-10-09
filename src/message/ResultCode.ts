// Add new result codes as you wish, but always keep 0 for success
// and keep these codes synchronized with workers implementations

export enum ResultCodeGroup {
    SUCCESS = 0,
    NON_STANDARD = 1,
    MESSAGE_ERROR = 2,
    BRIDGE_ERROR = 3,
    WORKER_ERROR = 4,
}

export enum ResultCode {
    // OK
    SUCCESS = 0,

    // NON_STANDARD: 1000+
    REPEAT = 1001,
    FORWARD_TO_TARGET_QUEUE = 1002,

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

    // WORKER_ERRORS: 4000+
    WORKER_TIMEOUT = 4001,
}
