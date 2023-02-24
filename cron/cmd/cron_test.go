package main

import (
	"testing"
	"time"
)

func TestCron(t *testing.T) {
	go main()

	time.Sleep(time.Second)
}
