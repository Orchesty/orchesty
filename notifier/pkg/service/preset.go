package service

import (
	"context"
	"notifier/pkg/model"
)

func BuildPresets() []model.Preset {
	return []model.Preset{
		{
			ID:          "topology_failed",
			Enabled:     true,
			Description: "topology finished with errors",
			Match: func(_ context.Context, e model.EventEnvelope, _ model.EvaluatorHelpers) (bool, error) {
				return e.EventType == "topology_failed", nil
			},
		},
		{
			ID:          "topology_failed_repeatedly",
			Enabled:     true,
			Description: ">=3 failures in 10m for same topology",
			Match: func(ctx context.Context, e model.EventEnvelope, h model.EvaluatorHelpers) (bool, error) {
				if e.EventType != "topology_failed" || e.Topology == nil {
					return false, nil
				}

				count, err := h.WindowCount(ctx, "fail:"+e.Topology.ID, 10*60)
				if err != nil {
					return false, err
				}

				return count >= 3, nil
			},
		},
		{
			ID:          "topology_failed_message",
			Enabled:     true,
			Description: "message moved to trash",
			Match: func(_ context.Context, e model.EventEnvelope, _ model.EvaluatorHelpers) (bool, error) {
				return e.EventType == "topology_failed_message", nil
			},
		},
		{
			ID:          "topology_slow",
			Enabled:     true,
			Description: "run duration over 5 minutes",
			Match: func(_ context.Context, e model.EventEnvelope, _ model.EvaluatorHelpers) (bool, error) {
				if e.EventType != "topology_slow" || e.Run == nil {
					return false, nil
				}

				return e.Run.DurationMs > 5*60*1000, nil
			},
		},
		{
			ID:          "limit_overflow",
			Enabled:     true,
			Description: "resource or message limit exceeded, messages are being discarded",
			Match: func(_ context.Context, e model.EventEnvelope, _ model.EvaluatorHelpers) (bool, error) {
				return e.EventType == "limit_overflow", nil
			},
		},
		{
			ID:          "limit_recovered",
			Enabled:     true,
			Description: "resource or message limit recovered",
			Match: func(_ context.Context, e model.EventEnvelope, _ model.EvaluatorHelpers) (bool, error) {
				return e.EventType == "limit_recovered", nil
			},
		},
	}
}
