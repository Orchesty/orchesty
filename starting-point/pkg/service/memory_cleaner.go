package service

import (
	"runtime/debug"
	"time"

	"starting-point/pkg/config"
	"starting-point/pkg/utils"
)

// StartCleaner starts memory cleaner
func StartCleaner() {
	timer := time.NewTicker(time.Second * time.Duration(config.Cleaner.CleanUp))

	go func() {
		t := 0.0
		total, err := utils.GetCurrentCPUTimeStat()
		if err == nil {
			t = total.Total()
		}

		for range timer.C {
			percentCPU, newT := utils.GetCPUUsage(t, int(config.Cleaner.CleanUp))
			t = newT

			if percentCPU <= float64(config.Cleaner.CPUPercentLimit) {
				debug.FreeOSMemory()
			}
		}
	}()
}
