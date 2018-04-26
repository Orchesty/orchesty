package generator

const (
	hour = "hour"
	day = "day"
	week = "week"
	month = "month"
	year = "year"
)

// TODO - test
func PopulateWait(cc *composedConfig, all []*composedConfig) error {
	inSeconds := convertToSeconds(int(cc.ec.Settings.Wait.Duration), cc.ec.Settings.Wait.Unit)

	for _, step := range cc.wfc.Steps {
		step.Wait.Duration = int32(inSeconds)
	}

	return PopulateDefault(cc, all)
}

func convertToSeconds(duration int, unit string) int {
	var durInSeconds int

	switch unit {
	case hour:
		durInSeconds = duration * 60
	case day:
		durInSeconds = duration * 60 * 24
	case week:
		durInSeconds = duration * 60 * 24 * 7
	case month:
		durInSeconds = duration * 60 * 24 * 7 * 30
	case year:
		durInSeconds = duration * 60 * 24 * 7 * 30 * 365
	default:
		durInSeconds = duration
	}

	return durInSeconds
}
