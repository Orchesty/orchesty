package rabbitmq

import (
	"github.com/stretchr/testify/assert"
	"github.com/stretchr/testify/mock"
	"github.com/stretchr/testify/require"
	"testing"
)

type fakeConnect struct{}

func (_ fakeConnect) connect() {}

type mockClient struct {
	client
	mock.Mock
	t *testing.T
}

func (mc *mockClient) connect() {
	mc.Called()
	require.NotNil(mc.t, mc.connection)
	assert.False(mc.t, mc.connection.IsClosed())

	_ = mc.connection.Close()
}

func TestClient_HandleReconnect(t *testing.T) {
	client := &mockClient{t: t}
	client.On("connect").Return()

	client.handleReconnect(client, "amqp://rabbitmq")

	// TODO tady musíme volat kubovo něco, co shodí spojení a upravit test, že "connect" se zavolalo 2x

	client.AssertExpectations(t)
}
