package limiter

import (
	"limiter/pkg/logger"
	"limiter/pkg/storage"

	"github.com/stretchr/testify/assert"

	"testing"
	"time"
)

type distinctFinderMock struct{}

func (fm *distinctFinderMock) GetDistinctFirstItems() (map[string]*storage.Message, error) {
	items := make(map[string]*storage.Message)
	items["new"] = &storage.Message{
		LimitKey: "new",
		Created:  time.Now(),
	}
	items["fresh"] = &storage.Message{
		LimitKey: "fresh",
		Created:  time.Now().Add(-time.Minute * 5),
	}
	items["rotten"] = &storage.Message{
		LimitKey: "rotten",
		Created:  time.Now().Add(-time.Hour * 25),
	}

	return items, nil
}

// test if after calling the Check the appropriate messages are blacklisted
func TestLimitGuard_IsOnBlacklist(t *testing.T) {
	guard := NewLimitGuard(&distinctFinderMock{}, logger.GetNullLogger())

	// until check is called blacklist should be empty
	assert.False(t, guard.IsOnBlacklist("new"))
	assert.False(t, guard.IsOnBlacklist("fresh"))
	assert.False(t, guard.IsOnBlacklist("rotten"))
	assert.False(t, guard.IsOnBlacklist("invalid"))

	// messages older then 24h should be blacklisted
	guard.Check(time.Hour * 24)

	assert.False(t, guard.IsOnBlacklist("new"))
	assert.False(t, guard.IsOnBlacklist("fresh"))
	assert.True(t, guard.IsOnBlacklist("rotten"))

	// messages older then 10s should be blacklisted now
	guard.Check(time.Second * 10)
	assert.False(t, guard.IsOnBlacklist("new"))
	assert.True(t, guard.IsOnBlacklist("fresh"))
	assert.True(t, guard.IsOnBlacklist("rotten"))
}
