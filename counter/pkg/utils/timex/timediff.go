package timex

import "time"

func MsDiff(from, to time.Time) int {
	a := from.UnixNano()
	b := to.UnixNano()

	return int((b - a) / 1_000_000)
}
