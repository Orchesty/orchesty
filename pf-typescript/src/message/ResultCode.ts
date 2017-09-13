// Add new result codes as wished, but always keep 0 for success

export enum ResultCode {
    SUCCESS = 0,
    WORKER_TIMEOUT = 1,
    HTTP_ERROR = 2,
    INVALID_MESSAGE_CONTENT_FORMAT = 3,
    UNKNOWN_ERROR = 254,
    NOT_PROCESSED = 255,
}
