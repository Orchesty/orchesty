package model

import (
	"fmt"
	"github.com/hanaboso/pipes/counter/pkg/enum"
	"strconv"
	"time"
)

type ProcessMessage struct {
	Body    []byte
	Headers map[string]interface{}
	Tag     uint64
}

type ProcessBody struct {
	Success   bool `json:"success"`
	Following int  `json:"following"`
}

func (pm ProcessMessage) GetHeader(header enum.HeaderType) (string, error) {
	value, ok := pm.Headers[enum.PrefixHeader(string(header))]
	if !ok {
		return "", fmt.Errorf("requested header [%s] does not exist", header)
	}

	return fmt.Sprint(value), nil
}

func (pm ProcessMessage) GetHeaderOrDefault(header enum.HeaderType, defaultValue string) string {
	value, err := pm.GetHeader(header)
	if err != nil {
		return defaultValue
	}

	return value
}

func (pm ProcessMessage) GetIntHeaderOrDefault(header enum.HeaderType, defaultValue int) int {
	value, err := pm.GetHeader(header)
	if err != nil {
		return defaultValue
	}

	val, err := strconv.Atoi(value)
	if err != nil {
		return defaultValue
	}

	return val
}

func (pm ProcessMessage) GetTimeHeaderOrDefault(header enum.HeaderType) time.Time {
	value, err := pm.GetHeader(header)
	if err != nil {
		return time.Now()
	}

	val, err := strconv.ParseInt(value, 10, 64)
	if err != nil {
		return time.Now()
	}

	return time.Unix(0, val*1_000_000)
}

func (pm ProcessMessage) IntoProcess() *Process {
	return &Process{
		TopologyId:    pm.GetHeaderOrDefault(enum.Header_TopologyId, ""),
		CorrelationId: pm.GetHeaderOrDefault(enum.Header_CorrelationId, ""),
		ProcessId:     pm.GetHeaderOrDefault(enum.Header_ProcessId, ""),
		Created:       pm.GetTimeHeaderOrDefault(enum.Header_ProcessStarted),
		OpenProcesses: 0,
		Subprocesses:  make(map[string]*Subprocess),
		LastUpdate:    time.Now(),
	}
}

func (pm ProcessMessage) IntoSubprocess() *Subprocess {
	return &Subprocess{
		ParentProcess: pm.GetHeaderOrDefault(enum.Header_ParentProcessId, ""),
		Total:         1,
	}
}
