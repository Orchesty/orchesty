package metrics

import (
	"github.com/stretchr/testify/assert"
	"testing"
)

func TestInitFields(t *testing.T) {
	i := InitFields()

	assert.IsType(t, float64(0), i["timestamp"])
	assert.IsType(t, float64(0), i["fpm_cpu_user_time"])
	assert.IsType(t, float64(0), i["fpm_cpu_kernel_time"])
}

func TestGetFields(t *testing.T) {
	i := InitFields()
	r := GetFields(i)

	assert.IsType(t, float64(0), r["fpm_request_total_duration"])
	assert.IsType(t, float64(0), r["fpm_cpu_user_time"])
	assert.IsType(t, float64(0), r["fpm_cpu_kernel_time"])
}
