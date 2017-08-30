// Add new result codes as wished, but always keep 0 for success

export enum ResultCode {
    SUCCESS = 0,
    WORKER_TIMEOUT = 1,
    HTTP_ERROR = 2,
    UNKNOWN_ERROR = 255,
}
