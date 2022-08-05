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
func InitFields() (m map[string]float64) {
	m = make(map[string]float64)
	m[timestamp] = float64(Now())
	user, kernel := GetCPUTime()
	m[userTime] = user
	m[kernelTime] = kernel

	return
}

// GetFields for metrics
func GetFields(init map[string]float64) (m map[string]interface{}) {
	m = make(map[string]interface{})
	user, kernel := GetCPUTime()

	m[userTime] = user - init[userTime]
	m[kernelTime] = kernel - init[kernelTime]
	m[requestDuration] = float64(Now()) - init[timestamp]
	m[created] = time.Now().Unix()

	return
}

func Now() int64 {
	return time.Now().UnixNano() / 1_000_000
}
