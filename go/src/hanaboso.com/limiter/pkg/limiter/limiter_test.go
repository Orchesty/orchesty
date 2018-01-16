package limiter

import (
	"testing"
	"hanaboso.com/limiter/pkg/storage"
	"fmt"
	"github.com/stretchr/testify/assert"
)

type checkerSaverMock struct {}
func (db *checkerSaverMock) Check(key string) (bool, error) {
	if key == "on-negative" {
		return false, nil
	}
	if key == "on-error" {
		return false, fmt.Errorf("some error")
	}
	return true, nil
}
func (db *checkerSaverMock) Save(m *storage.Message) (string, error) {
	return "msgKey", nil
}

// TestIsFreeLimit tests the function using checkerSaver mock object
func TestIsFreeLimit(t *testing.T) {
	l := limiter{storage: &checkerSaverMock{}}

	res, err := l.IsFreeLimit("on-negative", "10", "10")
	assert.Nil(t, err)
	assert.False(t, res)

	res, err = l.IsFreeLimit("on-positive","10", "10")
	assert.Nil(t, err)
	assert.True(t, res)

	res, err = l.IsFreeLimit("on-error", "10", "10")
	assert.Equal(t, "some error", err.Error() )
	assert.False(t, res)
}