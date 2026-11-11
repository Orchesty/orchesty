package topology

import (
	"os"
	"path/filepath"
	"testing"
	"time"

	"github.com/stretchr/testify/assert"
	"github.com/stretchr/testify/require"
)

func TestParserV2_TimeoutInSeconds(t *testing.T) {
	dir := t.TempDir()
	path := filepath.Join(dir, "topology.json")
	require.NoError(t, os.WriteFile(path, []byte(`{
		"id": "t1",
		"name": "timeout",
		"nodes": [
			{"id": "n1", "name": "start", "worker": "worker.null", "settings": {"timeout": 30}, "followers": [{"id": "n2", "name": "conn"}]},
			{"id": "n2", "name": "conn", "worker": "worker.http", "settings": {"timeout": 45}, "followers": []}
		],
		"rabbitMq": [{"dsn": "amqp://rabbitmq:5672/%2F"}]
	}`), 0o600))

	topology, err := NewTopologySvc().Parse(path)
	require.NoError(t, err)

	assert.Equal(t, 45*time.Second, topology.Timeout)
}
