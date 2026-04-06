package service

import (
	"fmt"
	"net/http"

	log "github.com/hanaboso/go-log/pkg"

	"notifier/pkg/model"
	"notifier/pkg/sender"
)

type (
	DispatcherService interface {
		Dispatch(presetID string, e model.EventEnvelope, channelRecipients []model.ChannelRecipients) error
	}

	dispatcherService struct {
		sender sender.HttpSender
		urls   map[string]string
		logger log.Logger
	}
)

func NewDispatcherService(httpSender sender.HttpSender, urls map[string]string, logger log.Logger) DispatcherService {
	return dispatcherService{httpSender, urls, logger}
}

func (service dispatcherService) Dispatch(presetID string, e model.EventEnvelope, channelRecipients []model.ChannelRecipients) error {
	var lastErr error

	for _, cr := range channelRecipients {
		url, ok := service.urls[cr.Channel]
		if !ok || url == "" {
			service.logContext().Warn("No dispatch URL configured for channel %s, skipping", cr.Channel)

			continue
		}

		payload := model.DispatchPayload{
			PresetID:   presetID,
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

func (service dispatcherService) logContext() log.Logger {
	return service.logger.WithFields(map[string]interface{}{
		"service": "NOTIFIER",
		"type":    "Dispatcher",
	})
}
