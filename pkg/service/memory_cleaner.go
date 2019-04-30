package service

import (
	"runtime/debug"
	"starting-point/pkg/config"
	"starting-point/pkg/utils"
	"time"
)

// StartCleaner starts memory cleaner
func StartCleaner() {
	timer := time.NewTicker(time.Second * time.Duration(config.Config.Cleaner.CleanUp))

	go func() {
		t := 0.0
		total, err := utils.GetCurrentCPUTimeStat()
		if err == nil {
			t = total.Total()
		}

		for range timer.C {
			percentCPU, newT := utils.GetCPUUsage(t, int(config.Config.Cleaner.CleanUp))
			t = newT

			if percentCPU <= float64(config.Config.Cleaner.CPUPercentLimit) {
				RabbitMq.ClearChannels()
				debug.FreeOSMemory()
			}
		}
	}()
}
