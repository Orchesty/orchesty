package limiter

import (
	"testing"
	"hanaboso.com/limiter/pkg/storage"
	"fmt"
	"github.com/stretchr/testify/assert"
	"hanaboso.com/limiter/pkg/logger"
)

type checkerSaverMock struct{}

func (db *checkerSaverMock) Exists(key string) (bool, error) {
	if key == "when-not-exists" {
		return true, nil
	}
	if key == "on-error" {
		return true, fmt.Errorf("some error")
	}
	return false, nil
}
func (db *checkerSaverMock) CanHandle(key string, time int, value int) (bool, error) {
	return db.Exists(key)
}
func (db *checkerSaverMock) Save(m *storage.Message) (string, error) {
	return "msgKey", nil
}

// TestIsFreeLimit tests the function using checkerSaver mock object
func TestLimiter_IsFreeLimit(t *testing.T) {
	l := limiter{store: &checkerSaverMock{}, logger: logger.GetNullLogger()}

	res, err := l.IsFreeLimit("when-not-exists", 10, 10)
	assert.Nil(t, err)
	assert.True(t, res)

	res, err = l.IsFreeLimit("when-exists", 10, 10)
	assert.Nil(t, err)
	assert.False(t, res)

	res, err = l.IsFreeLimit("on-error", 10, 10)
	assert.Equal(t, "some error", err.Error())
	assert.False(t, res)
}
