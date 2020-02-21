package services_test

import (
	"testing"

	"github.com/stretchr/testify/require"
	"rabbitmq-telegraf/pkg/services"
)

func TestInfluxDbSender_Send(t *testing.T) {
	svc := services.NewInfluxDbSenderSvc()
	msgs := []services.Queue{
		{
			Name:     "queue",
			Messages: 1,
		},
		{
			Name:     "queue2",
			Messages: 12,
		},
	}

	err := svc.Send(msgs)
	require.Nil(t, err)
}
