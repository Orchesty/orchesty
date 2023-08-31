package storage

import (
	"testing"
	"time"

	"github.com/stretchr/testify/assert"
)

func getGroups() map[string]*customerInfo {
	groups := make(map[string]*customerInfo, 3)
	groups["SHOP-A"] = &customerInfo{
		interval: 4,
		count:    10,
		last:     time.Time{},
	}
	groups["SHOP-B"] = &customerInfo{
		interval: 4,
		count:    10,
		last:     time.Time{},
	}
	groups["SHOP-C"] = &customerInfo{
		interval: 4,
		count:    10,
		last:     time.Time{},
	}

	return groups
}

func TestGroupCache_canHandle(t *testing.T) {
	t.Run("Group limit not excited", func(t *testing.T) {
		g := groupCache{
			cacheItem: &cacheItem{
				ticker: nil,
				max:    40,
				count:  0,
			},
			Groups: getGroups(),
		}
		assert.True(t, g.canHandle("SHOP-A", 8, 10))
	})
}

func TestGroupCache_canHandle2(t *testing.T) {
	t.Run("Group limit with missing shop in accessed", func(t *testing.T) {
		g := groupCache{
			cacheItem: &cacheItem{
				ticker: nil,
				max:    40,
				count:  0,
			},
			Groups: getGroups(),
		}
		assert.False(t, g.canHandle("SHOP-D", 8, 10))
	})

	t.Run("Group limit with empty group shops", func(t *testing.T) {
		gg := groupCache{
			cacheItem: &cacheItem{
				ticker: nil,
				max:    40,
				count:  0,
			},
			Groups: nil,
		}
		assert.True(t, gg.canHandle("SHOP-A", 8, 10))
	})
}

func TestGroupCache_canHandle3(t *testing.T) {
	t.Run("Group limit with empty group shops", func(t *testing.T) {
		gg := groupCache{
			cacheItem: &cacheItem{
				ticker: nil,
				max:    40,
				count:  0,
			},
			Groups: nil,
		}
		assert.True(t, gg.canHandle("SHOP-A", 8, 10))
	})
}

func TestGroupCache_canHandle4(t *testing.T) {
	t.Run("Group limit excited for third", func(t *testing.T) {
		g := groupCache{
			cacheItem: &cacheItem{
				ticker: nil,
				max:    40,
				count:  0,
			},
			Groups: getGroups(),
		}
		assert.False(t, g.canHandle("SHOP-C", 8, 10))
	})
}

func TestGroupCache_handleRequest(t *testing.T) {
	refTime := time.Date(2021, time.January, 19, 12, 0, 0, 0, &time.Location{})

	g := groupCache{
		cacheItem: &cacheItem{
			ticker: nil,
			max:    40,
			count:  0,
		},
		Groups: map[string]*customerInfo{
			"SHOP-A": {
				interval: 4,
				count:    10,
				last:     refTime,
			},
		},
	}
	t.Run("Add exist group request", func(t *testing.T) {
		nowTime := time.Date(2021, time.January, 19, 20, 0, 0, 0, &time.Location{})
		g.handleRequest("SHOP-A", 10, 100, nowTime)

		assert.Equal(t, 4, g.Groups["SHOP-A"].interval)
		assert.Equal(t, 10, g.Groups["SHOP-A"].count)
		assert.Equal(t, nowTime, g.Groups["SHOP-A"].last)
	})

	t.Run("Add new group request", func(t *testing.T) {
		nowTime := time.Date(2021, time.January, 19, 20, 0, 0, 0, &time.Location{})
		g.handleRequest("SHOP-B", 10, 100, nowTime)

		assert.Equal(t, 10, g.Groups["SHOP-B"].interval)
		assert.Equal(t, 100, g.Groups["SHOP-B"].count)
		assert.Equal(t, nowTime, g.Groups["SHOP-B"].last)
	})

}
