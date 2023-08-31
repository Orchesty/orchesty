package model

import (
	"fmt"
	"strconv"
	"strings"
)

// RequestGroup - holder for request group
type RequestGroup struct {
	Key      string
	Interval int
	Count    int
}

// LimiterRow - holder for amq message
type LimiterRow struct {
	Name  string
	ID    string
	Base  *RequestGroup
	Group *RequestGroup
}

// ParseLimiterRow - represent limit row
func ParseLimiterRow(msg string) (LimiterRow, error) {
	result := LimiterRow{}

	msg = strings.TrimSpace(msg)
	data := strings.Split(msg, ";")

	if len(data) == 0 {
		return LimiterRow{}, fmt.Errorf("message has no fileds")
	}

	// matches is ready request message
	if len(data) == 2 {
		result.Name = data[0]
		result.ID = data[1]

		return result, nil
	}

	//TODO: refactor
	if data[0] == "check" {
		// matches check limit request message
		if len(data) == 5 || len(data) == 8 {
			result.Name = data[0]
			result.ID = data[1]

			timeParam, err := strconv.Atoi(data[3])
			if err != nil {
				return result, fmt.Errorf("invalid Interval param " + err.Error())
			}

			valueParam, err := strconv.Atoi(data[4])
			if err != nil {
				return result, fmt.Errorf("invalid Count param " + err.Error())
			}

			result.Base = &RequestGroup{
				Key:      data[2],
				Interval: timeParam,
				Count:    valueParam,
			}

			if len(data) == 5 {
				return result, nil
			}

			timeGroupParam, err := strconv.Atoi(data[6])
			if err != nil {
				return result, fmt.Errorf("invalid group Interval param " + err.Error())
			}

			valueGroupParam, err := strconv.Atoi(data[7])
			if err != nil {
				return result, fmt.Errorf("invalid group Count param " + err.Error())
			}
			result.Group = &RequestGroup{
				Key:      data[5],
				Interval: timeGroupParam,
				Count:    valueGroupParam,
			}

			return result, nil
		}
	} else {
		if len(data) == 3 || len(data) == 6 {
			result.Name = "check"
			result.ID = ""

			timeParam, err := strconv.Atoi(data[1])
			if err != nil {
				return result, fmt.Errorf("invalid Interval param " + err.Error())
			}

			valueParam, err := strconv.Atoi(data[2])
			if err != nil {
				return result, fmt.Errorf("invalid Count param " + err.Error())
			}

			result.Base = &RequestGroup{
				Key:      data[0],
				Interval: timeParam,
				Count:    valueParam,
			}

			if len(data) == 3 {
				return result, nil
			}

			timeGroupParam, err := strconv.Atoi(data[4])
			if err != nil {
				return result, fmt.Errorf("invalid group Interval param " + err.Error())
			}

			valueGroupParam, err := strconv.Atoi(data[5])
			if err != nil {
				return result, fmt.Errorf("invalid group Count param " + err.Error())
			}
			result.Group = &RequestGroup{
				Key:      data[3],
				Interval: timeGroupParam,
				Count:    valueGroupParam,
			}

			return result, nil
		}
	}

	return result, fmt.Errorf("unknown number of params")
}
