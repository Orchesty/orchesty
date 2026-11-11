package utils

import (
	"os"

	"github.com/shirou/gopsutil/cpu"
	"github.com/shirou/gopsutil/process"
)

var p *process.Process

// GetCPUTime returns user & kernel CPU time
func GetCPUTime() (user float64, kernel float64) {
	cpuTimes, err := GetCurrentCPUTimeStat()
	if err != nil {
		return 0.0, 0.0
	}

	return cpuTimes.User, cpuTimes.Total() - cpuTimes.User
}

// GetCPUUsage returns CPU usage
func GetCPUUsage(beforeTime float64, duration int) (usage float64, newTotal float64) {
	t, err := GetCurrentCPUTimeStat()
	if err != nil {
		return 0.0, 0.0
	}

	newTotal = t.Total()
	diff := newTotal - beforeTime

	return (diff * 100) / float64(duration), newTotal
}

// GetCurrentCPUTimeStat returns CPU time stats
func GetCurrentCPUTimeStat() (t *cpu.TimesStat, err error) {
	proc, err := getProcess()
	if err != nil {
		return nil, err
	}

	return proc.Times()
}

func getProcess() (proc *process.Process, err error) {
	if p != nil {
		return p, nil
	}

	proc, err = process.NewProcess(int32(os.Getpid()))
	if err != nil {
		return nil, err
	}

	p = proc
	return p, nil
}
