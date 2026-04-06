package service

import (
	"context"
	"encoding/json"
	"fmt"

	log "github.com/hanaboso/go-log/pkg"

	"notifier/pkg/config"
	"notifier/pkg/model"
)

type (
	ProcessorService interface {
		Process(ctx context.Context, body []byte) error
	}

	processorService struct {
		presets    []model.Preset
		helpers    model.EvaluatorHelpers
		throttle   ThrottleStore
		recipient  RecipientService
		dispatcher DispatcherService
		logger     log.Logger
	}
)

func NewProcessorService(
	presets []model.Preset,
	helpers model.EvaluatorHelpers,
	throttle ThrottleStore,
	recipient RecipientService,
	dispatcher DispatcherService,
	logger log.Logger,
) ProcessorService {
	return processorService{presets, helpers, throttle, recipient, dispatcher, logger}
}

func (service processorService) Process(ctx context.Context, body []byte) error {
	var e model.EventEnvelope

	if err := json.Unmarshal(body, &e); err != nil {
		service.logContext().Error(fmt.Errorf("failed to parse event: %v", err))

		return fmt.Errorf("failed to parse event: %w", err)
	}

	service.logContext().Debug("Received event %s type=%s", e.EventID, e.EventType)

	notifs := service.evaluatePresets(ctx, e)
	if len(notifs) == 0 {
		return nil
	}

	service.logContext().Debug("Matched %d presets", len(notifs))

	for _, n := range notifs {
		key := throttleKey(n.PresetID, e)
		blocked, err := service.throttle.ThrottleOnce(ctx, key, config.Throttle.WindowMs)

		if err != nil {
			service.logContext().Error(fmt.Errorf("throttle error: %v", err))

			continue
		}

		if blocked {
			service.logContext().Debug("Throttled preset=%s key=%s", n.PresetID, key)

			continue
		}

		channelRecipients, err := service.recipient.ResolveForEvent(e)
		if err != nil {
			service.logContext().Error(fmt.Errorf("recipient resolution error: %v", err))
		}

		if len(channelRecipients) == 0 {
			service.logContext().Debug("No recipients for preset=%s, skipping dispatch", n.PresetID)

			continue
		}

		if err := service.dispatcher.Dispatch(n.PresetID, e, channelRecipients); err != nil {
			service.logContext().Error(fmt.Errorf("dispatch error for preset=%s: %v", n.PresetID, err))
		}
	}

	return nil
}

func (service processorService) evaluatePresets(ctx context.Context, e model.EventEnvelope) []model.NotificationMessage {
	var results []model.NotificationMessage

	for _, p := range service.presets {
		if !p.Enabled {
			continue
		}

		ok, err := p.Match(ctx, e, service.helpers)
		if err != nil {
			service.logContext().Error(err)

			continue
		}

		if !ok {
			continue
		}

		results = append(results, model.NotificationMessage{
			PresetID: p.ID,
			Event:    e,
		})
	}

	return results
}

func throttleKey(presetID string, e model.EventEnvelope) string {
	if config.Throttle.Mode == "global_per_preset" {
		return fmt.Sprintf("throttle:%s:%s", e.TenantID, presetID)
	}

	topoID := "no-topo"
	if e.Topology != nil {
		topoID = e.Topology.ID
	}

	return fmt.Sprintf("throttle:%s:%s:%s", e.TenantID, presetID, topoID)
}

func (service processorService) logContext() log.Logger {
	return service.logger.WithFields(map[string]interface{}{
		"service": "NOTIFIER",
		"type":    "Processor",
	})
}
