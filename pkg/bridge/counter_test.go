package bridge

import (
	"testing"

	"github.com/hanaboso/pipes/bridge/pkg/enum"
	"github.com/hanaboso/pipes/bridge/pkg/model"
	"github.com/hanaboso/pipes/bridge/pkg/utils/timex"
	amqp "github.com/rabbitmq/amqp091-go"
	"github.com/stretchr/testify/assert"
)

type recordingPublisher struct {
	last amqp.Publishing
}

func (p *recordingPublisher) Publish(message amqp.Publishing) error {
	p.last = message

	return nil
}

func (p *recordingPublisher) PublishBatch(messages []amqp.Publishing) error {
	if len(messages) > 0 {
		p.last = messages[len(messages)-1]
	}

	return nil
}

func TestCounter_SendStampsPublishedAndProcessedTimestamps(t *testing.T) {
	publisher := &recordingPublisher{}
	c := counter{publisher: publisher}

	before := timex.UnixMs()
	c.send(model.OkResult(&model.ProcessMessage{Published: 1_700_000_000_000}), 1)
	after := timex.UnixMs()

	published, ok := publisher.last.Headers[enum.Header_PublishedTimestamp].(int64)
	assert.True(t, ok, "published-timestamp header must be an int64")
	assert.Equal(t, int64(1_700_000_000_000), published)

	processed, ok := publisher.last.Headers[enum.Header_ProcessedTimestamp].(int64)
	assert.True(t, ok, "processed-timestamp header must be an int64")
	assert.GreaterOrEqual(t, processed, published)
	assert.GreaterOrEqual(t, processed, before)
	assert.LessOrEqual(t, processed, after)
}

func TestCounter_SendFallsBackToNowWhenPublishedMissing(t *testing.T) {
	publisher := &recordingPublisher{}
	c := counter{publisher: publisher}

	before := timex.UnixMs()
	c.send(model.OkResult(&model.ProcessMessage{}), 1)

	published, ok := publisher.last.Headers[enum.Header_PublishedTimestamp].(int64)
	assert.True(t, ok, "published-timestamp header must be an int64")
	assert.GreaterOrEqual(t, published, before)

	processed, ok := publisher.last.Headers[enum.Header_ProcessedTimestamp].(int64)
	assert.True(t, ok, "processed-timestamp header must be an int64")
	assert.GreaterOrEqual(t, processed, published)
}
