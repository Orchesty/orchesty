package services

import (
	"github.com/stretchr/testify/assert"
	"strings"
	"testing"
)

func TestNewInfluxDbSenderSvc_getUrl(t *testing.T) {
	svc := NewInfluxDbSenderSvc().(*InfluxDbSender)
	assert.Equal(t, "http://kapacitor:9092?db=pipes&rp=default", svc.getUrl())
}

func TestNewInfluxDbSenderSvc_prepData(t *testing.T) {
	svc := NewInfluxDbSenderSvc().(*InfluxDbSender)
	msgs := []Queue{
		{
			Name:     "queue",
			Messages: 1,
		},
		{
			Name:     "queue2",
			Messages: 12,
		},
	}

	assert.True(t, strings.Contains(svc.prepData(msgs[0]), "rabbitmq_queue,queue=queue messages=1"))
	assert.True(t, strings.Contains(svc.prepData(msgs[1]), "rabbitmq_queue,queue=queue2 messages=12"))
}
