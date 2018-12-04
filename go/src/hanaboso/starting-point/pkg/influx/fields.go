package influx

var cpu map[string]interface{}

const keyTimestamp = "timestamp"
const keyCPU = "cpu"
const keyRequestDuration = "request_duration"
const keyUserTime = "user_time"
const keyKernelTime = "kernel_time"

// Init metrics
func Init() {

}
