package service

import (
	"fmt"
	"net/http"
	"strings"

	log "github.com/hanaboso/go-log/pkg"

	"notifier/pkg/model"
	"notifier/pkg/sender"
)

var channelPaths = map[string]string{
	"email":  "/topologies/system-email-notifications/nodes/%s/run-by-name",
	"slack":  "/topologies/system-slack-notifications/nodes/%s/run-by-name",
	"in_app": "/topologies/system-cloud-notifications/nodes/cloud-notification-event/run-by-name",
}

type (
	DispatcherService interface {
		Dispatch(presetID string, e model.EventEnvelope, channelRecipients []model.ChannelRecipients) error
		DispatchBuffered(presetID string, firstEvent model.EventEnvelope, events []model.BufferedEvent, channelRecipients []model.ChannelRecipients) error
	}

	dispatcherService struct {
		sender     sender.HttpSender
		baseURL    string
		instanceID string
		logger     log.Logger
	}
)

// NewDispatcherService constructs the dispatcher. `instanceID` is the cloud-side
// UUID of this Orchesty instance (sourced from `ORCHESTY_CLOUD_INSTANCE_ID`)
// and is attached to every dispatched payload so cloud-backend can resolve the
// Instance row independently of the in-pipes tenant_id concept. May be empty
// for on-prem deployments where no cloud linkage exists.
func NewDispatcherService(httpSender sender.HttpSender, baseURL, instanceID string, logger log.Logger) DispatcherService {
	return dispatcherService{httpSender, baseURL, instanceID, logger}
}

func (service dispatcherService) Dispatch(presetID string, e model.EventEnvelope, channelRecipients []model.ChannelRecipients) error {
	var lastErr error

	nodeName := strings.ReplaceAll(presetID, "_", "-")

	for _, cr := range channelRecipients {
		url, ok := resolveURL(service.baseURL, cr.Channel, nodeName)
		if !ok {
			service.logContext().Warn("No dispatch URL configured for channel %s, skipping", cr.Channel)

			continue
		}

		payload := model.DispatchPayload{
			PresetID:   presetID,
			InstanceID: service.instanceID,
			TenantID:   e.TenantID,
			Channel:    cr.Channel,
			Event:      e,
			Recipients: cr.Recipients,
		}

		if _, err := service.sender.Send(http.MethodPost, url, payload); err != nil {
			service.logContext().Error(fmt.Errorf("dispatch to %s failed: %v", cr.Channel, err))
			lastErr = err
		} else {
			service.logContext().Debug("Dispatched to %s: %d recipients", cr.Channel, len(cr.Recipients))
		}
	}

	return lastErr
}

func (service dispatcherService) DispatchBuffered(presetID string, firstEvent model.EventEnvelope, events []model.BufferedEvent, channelRecipients []model.ChannelRecipients) error {
	var lastErr error

	nodeName := strings.ReplaceAll(presetID, "_", "-")

	for _, cr := range channelRecipients {
		url, ok := resolveURL(service.baseURL, cr.Channel, nodeName)
		if !ok {
			service.logContext().Warn("No dispatch URL configured for channel %s, skipping", cr.Channel)

			continue
		}

		payload := model.DispatchPayload{
			PresetID:   presetID,
			InstanceID: service.instanceID,
			TenantID:   firstEvent.TenantID,
			Channel:    cr.Channel,
			Event:      firstEvent,
			Events:     events,
			Recipients: cr.Recipients,
		}

		if _, err := service.sender.Send(http.MethodPost, url, payload); err != nil {
			service.logContext().Error(fmt.Errorf("dispatch to %s failed: %v", cr.Channel, err))
			lastErr = err
		} else {
			service.logContext().Debug("Dispatched buffered to %s: %d recipients, %d events", cr.Channel, len(cr.Recipients), len(events))
		}
	}

	return lastErr
}

func resolveURL(baseURL, channel, nodeName string) (string, bool) {
	if baseURL == "" {
		return "", false
	}

	pathTemplate, ok := channelPaths[channel]
	if !ok {
		return "", false
	}

	path := pathTemplate
	if strings.Contains(pathTemplate, "%s") {
		path = fmt.Sprintf(pathTemplate, nodeName)
	}

	return baseURL + path, true
}

func (service dispatcherService) logContext() log.Logger {
	return service.logger.WithFields(map[string]interface{}{
		"service": "NOTIFIER",
		"type":    "Dispatcher",
	})
}
