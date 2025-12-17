package utils

import (
	"time"
)

const requestDuration = "fpm_request_total_duration"
const userTime = "fpm_cpu_user_time"
const kernelTime = "fpm_cpu_kernel_time"
const timestamp = "timestamp"
const created = "created"

// InitFields metrics
func InitFields() (m map[string]interface{}) {
	m = make(map[string]interface{})
	m[timestamp] = float64(Now())
	user, kernel := GetCPUTime()
	m[userTime] = user
	m[kernelTime] = kernel

	return
}

// GetFields for metrics
func GetFields(init map[string]interface{}) (m map[string]interface{}) {
	m = make(map[string]interface{})
	user, kernel := GetCPUTime()

	m[userTime] = int(user - init[userTime].(float64))
	m[kernelTime] = int(kernel - init[kernelTime].(float64))
	m[requestDuration] = int(float64(Now()) - init[timestamp].(float64))
	m[created] = time.Now()

	return
}

func Now() int64 {
	return time.Now().UnixNano() / 1_000_000
}
