package generator

import ws "clever-monitor/workflow/pkg/workflowservice/clevermonitor/analytics/protos/workflow"

const (
	hour = "hour"
	day = "day"
	week = "week"
	month = "month"
	year = "year"
)

func PopulateWait(cc *composedConfig, all []*composedConfig) error {
	err := PopulateDefault(cc, all)
	if err != nil {
		return err
	}

	inSeconds := convertToSeconds(int(cc.ec.Settings.Wait.Duration), cc.ec.Settings.Wait.Unit)

	for _, step := range cc.wfc.Steps {
		step.Wait = &ws.WorkflowConfig_Step_Wait{
			Duration: int32(inSeconds),
		}
	}

	return nil
}

func convertToSeconds(duration int, unit string) int {
	var durInSeconds int

	switch unit {
	case hour:
		durInSeconds = duration * 3600
	case day:
		durInSeconds = duration * 3600 * 24
	case week:
		durInSeconds = duration * 3600 * 24 * 7
	case month:
		durInSeconds = duration * 3600 * 24 * 7 * 30
	case year:
		durInSeconds = duration * 3600 * 24 * 7 * 30 * 365
	default:
		durInSeconds = duration
	}

	return durInSeconds
}
