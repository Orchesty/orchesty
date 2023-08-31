package timex

import "time"

const Format = "2006-01-02 15:04:05"

func UnixMs() int64 {
	return time.Now().UnixNano() / 1_000_000
}

func NowFormatted() string {
	return time.Now().Format(Format)
}
