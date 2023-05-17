package model

import (
	"fmt"
	"github.com/hanaboso/go-utils/pkg/intx"
	"limiter/pkg/enum"
	"strings"
)

type MessageDto struct {
	Headers map[string]interface{} `json:"headers" bson:"headers"`
	Body    string                 `json:"body" bson:"body"`
}

func (this MessageDto) GetHeader(key string) string {
	value, ok := this.Headers[key]
	if !ok {
		value = ""
	}

	return fmt.Sprintf("%s", value)
}

func (this MessageDto) GetIntHeader(key string) int {
	value, ok := this.Headers[key]
	if !ok {
		return 0
	}

	return intx.ParseAny(value)
}

func (this MessageDto) RepeatDelay() int {
	repeated := this.GetIntHeader(enum.Header_ResultCode) == enum.ResultCode_Repeat
	if !repeated {
		return 0
	}

	return this.GetIntHeader(enum.Header_RepeatInterval)
}

func (this MessageDto) ParseLimitKeys() (string, []Limit, error) {
	limitKey := this.GetHeader(enum.Header_LimitKey)
	limits := ParseLimits(limitKey)
	targetApplication := this.GetHeader(enum.Header_Application)

	if targetApplication != "" {
		var usedLimits []Limit

		for _, limit := range limits {
			if limit.SystemKey == targetApplication {
				usedLimits = append(usedLimits, limit)
			}
		}

		if usedLimits == nil || len(usedLimits) <= 0 {
			return "", nil, fmt.Errorf("missing application limit for [%s]", targetApplication)
		}

		var usedLimitKeys []string
		for _, limit := range usedLimits {
			usedLimitKeys = append(usedLimitKeys, limit.LimitKey())
		}

		limitKey = strings.Join(usedLimitKeys, ";")
		limits = usedLimits
	}

	return limitKey, limits, nil
}
