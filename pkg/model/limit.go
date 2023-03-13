package model

import (
	"fmt"
	"github.com/hanaboso/go-utils/pkg/intx"
	"github.com/hanaboso/go-utils/pkg/stringx"
	"strings"
)

type Limit struct {
	FullKey       string
	SystemKey     string // Decides if this limit should be applied, when header contains current application used
	UserKey       string
	Time          int // Time period in seconds
	TimeToRefresh int // Remaining time before end of period
	Maximum       int // Max amount of requests per time period
	Allowed       int // Still available amount of request to be sent
	Running       int // Send requests that has not been resolved yet
	Empty         int // Counter for deletion
}

// key;time;amount
// user|application;1;1;user2|application;1;1

func (this Limit) LimitKey() string {
	return fmt.Sprintf("%s;%d;%d", this.FullKey, this.Time, this.Maximum)
}

func (this Limit) AllowedBatch() int {
	return intx.Min(this.Maximum/(9*this.Time)+1, this.Allowed, 30)
}

func ParseLimits(limitValue string) []Limit {
	limits := make([]Limit, 0)
	parts := strings.Split(limitValue, ";")
	for i := 0; i < len(parts); i += 3 {
		if len(parts) > i+2 {
			keys := strings.Split(parts[i], "|")
			refreshTime := intx.Parse(parts[i+1])
			maximum := intx.Parse(parts[i+2])

			limits = append(limits, Limit{
				FullKey:       parts[i],
				SystemKey:     keys[1],
				UserKey:       stringx.FromArray(keys, 0),
				Time:          refreshTime,
				TimeToRefresh: refreshTime,
				Maximum:       maximum,
				Allowed:       maximum,
			})
		}
	}

	return limits
}

func (this *Limit) FinishProcess() {
	this.Running = intx.Max(0, this.Running-1)
}

func (this *Limit) Refresh() bool {
	this.TimeToRefresh -= 1
	if this.TimeToRefresh <= 0 {
		if this.Allowed == this.Maximum {
			this.Empty += 1
			if this.Empty >= 3 {
				return true
			}
		}

		this.Empty = 0
		this.TimeToRefresh = this.Time
		this.Allowed = this.Maximum - this.Running
	}

	return false
}
