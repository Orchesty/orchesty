package service

import (
	"context"
	"encoding/json"
	"fmt"
	"time"

	log "github.com/hanaboso/go-log/pkg"

	"notifier/pkg/config"
	"notifier/pkg/model"
	"notifier/pkg/storage"
)

type (
	ProcessorService interface {
		Process(ctx context.Context, body []byte) error
	}

	processorService struct {
		presets        []model.Preset
		helpers        model.EvaluatorHelpers
		throttle       ThrottleStore
		buffer         BufferService
		recipient      RecipientService
		dispatcher     DispatcherService
		storage        storage.MongoStorage
		sseBroadcaster *SSEBroadcaster
		logger         log.Logger
	}
)

func NewProcessorService(
	presets []model.Preset,
	helpers model.EvaluatorHelpers,
	throttle ThrottleStore,
	buffer BufferService,
	recipient RecipientService,
	dispatcher DispatcherService,
	mongoStorage storage.MongoStorage,
	sseBroadcaster *SSEBroadcaster,
	logger log.Logger,
) ProcessorService {
	return processorService{presets, helpers, throttle, buffer, recipient, dispatcher, mongoStorage, sseBroadcaster, logger}
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

	service.processInApp(ctx, e, notifs)

	for _, n := range notifs {
		tKey := throttleKey(n.PresetID, e)
		blocked, err := service.throttle.IsThrottled(ctx, tKey)

		if err != nil {
			service.logContext().Error(fmt.Errorf("throttle error: %v", err))

			continue
		}

		if blocked {
			service.logContext().Debug("Throttled preset=%s key=%s", n.PresetID, tKey)

			continue
		}

		nodeName := ""
		if e.Node != nil {
			nodeName = e.Node.Name
		}

		entry := BufferEntry{
			NodeName:     nodeName,
			ErrorMessage: e.Message,
		}

		bKey := bufferKey(n.PresetID, e)
		isNew, err := service.buffer.Add(ctx, bKey, entry, e)

		if err != nil {
			service.logContext().Error(fmt.Errorf("buffer error: %v", err))

			continue
		}

		service.logContext().Debug("Buffered preset=%s node=%s isNew=%v", n.PresetID, nodeName, isNew)

		if isNew {
			go service.scheduleFlush(bKey, n.PresetID, tKey)
		}
	}

	return nil
}

func (service processorService) scheduleFlush(bKey, presetID, tKey string) {
	time.Sleep(time.Duration(config.Throttle.BufferWindow) * time.Second)

	service.logContext().Debug("Flushing buffer key=%s preset=%s", bKey, presetID)

	data, err := service.buffer.Flush(bKey)
	if err != nil {
		service.logContext().Error(fmt.Errorf("buffer flush error: %v", err))

		return
	}

	if data == nil || len(data.Entries) == 0 {
		service.logContext().Debug("Buffer empty after flush key=%s", bKey)

		return
	}

	channelRecipients, err := service.recipient.ResolveForEvent(data.FirstEvent)
	if err != nil {
		service.logContext().Error(fmt.Errorf("recipient resolution error: %v", err))
	}

	if len(channelRecipients) == 0 {
		service.logContext().Debug("No recipients for preset=%s after flush, skipping", presetID)

		return
	}

	events := make([]model.BufferedEvent, len(data.Entries))
	for i, entry := range data.Entries {
		events[i] = model.BufferedEvent{
			NodeName:     entry.NodeName,
			ErrorMessage: entry.ErrorMessage,
		}
	}

	if err := service.dispatcher.DispatchBuffered(presetID, data.FirstEvent, events, channelRecipients); err != nil {
		service.logContext().Error(fmt.Errorf("dispatch error for preset=%s: %v", presetID, err))
	}

	window := throttleWindowFor(presetID)
	if err := service.throttle.SetThrottle(context.Background(), tKey, window); err != nil {
		service.logContext().Error(fmt.Errorf("failed to set throttle after flush: %v", err))
	}

	service.logContext().Debug("Throttle set for %ds key=%s", window, tKey)
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

func (service processorService) processInApp(ctx context.Context, e model.EventEnvelope, notifs []model.NotificationMessage) {
	for _, n := range notifs {
		tKey := inAppThrottleKey(n.PresetID, e)
		blocked, err := service.throttle.IsThrottled(ctx, tKey)

		if err != nil {
			service.logContext().Error(fmt.Errorf("in_app throttle error: %v", err))
			continue
		}

		if blocked {
			service.logContext().Debug("In-app throttled preset=%s key=%s", n.PresetID, tKey)
			continue
		}

		notification := model.NewInAppNotification(e)

		if err := service.storage.SaveNotification(notification); err != nil {
			service.logContext().Error(fmt.Errorf("in_app save error: %v", err))
			continue
		}

		service.sseBroadcaster.Broadcast(notification)

		inAppRecipients := []model.ChannelRecipients{
			{Channel: "in_app", Recipients: nil},
		}
		if err := service.dispatcher.Dispatch(n.PresetID, e, inAppRecipients); err != nil {
			service.logContext().Error(fmt.Errorf("in_app dispatch error: %v", err))
		}

		window := config.Throttle.InAppThrottleWindow
		if n.PresetID == "cloud_limit_threshold" {
			window = config.Throttle.EmailWindow
		}
		if err := service.throttle.SetThrottle(ctx, tKey, window); err != nil {
			service.logContext().Error(fmt.Errorf("in_app throttle set error: %v", err))
		}
	}
}

// throttleWindowFor returns the email-throttle window for all "user-facing"
// presets (topology failures, limit signals, cloud-limit thresholds). They
// share `EmailWindow` (default 2h) so a misbehaving topology cannot spam the
// recipient — the buffer collects events during `BufferWindow` and the next
// email is gated for `EmailWindow` after the previous flush. The global
// `Window` is reserved as a fallback for any future preset that hasn't been
// explicitly classified yet.
func throttleWindowFor(presetID string) int {
	switch presetID {
	case "topology_failed",
		"topology_failed_repeatedly",
		"topology_failed_message",
		"topology_slow",
		"limit_overflow",
		"limit_recovered",
		"cloud_limit_threshold":
		return config.Throttle.EmailWindow
	}
	return config.Throttle.Window
}

func inAppThrottleKey(presetID string, e model.EventEnvelope) string {
	topoID := "no-topo"
	if e.Topology != nil {
		topoID = e.Topology.ID
	}

	return fmt.Sprintf("inapp:throttle:%s:%s:%s%s", e.TenantID, presetID, topoID, presetSuffix(presetID, e))
}

func throttleKey(presetID string, e model.EventEnvelope) string {
	suffix := presetSuffix(presetID, e)
	if config.Throttle.Mode == "global_per_preset" {
		return fmt.Sprintf("throttle:%s:%s%s", e.TenantID, presetID, suffix)
	}

	topoID := "no-topo"
	if e.Topology != nil {
		topoID = e.Topology.ID
	}

	return fmt.Sprintf("throttle:%s:%s:%s%s", e.TenantID, presetID, topoID, suffix)
}

func bufferKey(presetID string, e model.EventEnvelope) string {
	topoID := "no-topo"
	if e.Topology != nil {
		topoID = e.Topology.ID
	}

	return fmt.Sprintf("%s:%s:%s%s", e.TenantID, presetID, topoID, presetSuffix(presetID, e))
}

// presetSuffix scopes throttle/buffer keys for presets that need independent
// throttling per context dimension (e.g. cloud_limit_threshold splits keys
// per resource so a "messages" warning does not silence a later "storage"
// warning during the same 2h window).
func presetSuffix(presetID string, e model.EventEnvelope) string {
	if presetID != "cloud_limit_threshold" || e.Context == nil {
		return ""
	}

	resource, _ := e.Context["resource"].(string)
	if resource == "" {
		return ""
	}

	return ":" + resource
}

func (service processorService) logContext() log.Logger {
	return service.logger.WithFields(map[string]interface{}{
		"service": "NOTIFIER",
		"type":    "Processor",
	})
}
