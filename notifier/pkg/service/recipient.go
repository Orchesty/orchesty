package service

import (
	"fmt"

	log "github.com/hanaboso/go-log/pkg"
	"go.mongodb.org/mongo-driver/v2/bson"

	"notifier/pkg/model"
	"notifier/pkg/storage"
)

type (
	RecipientService interface {
		ResolveForEvent(e model.EventEnvelope) ([]model.ChannelRecipients, error)
	}

	recipientService struct {
		repository storage.MongoStorage
		logger     log.Logger
	}
)

func NewRecipientService(repository storage.MongoStorage, logger log.Logger) RecipientService {
	return recipientService{repository, logger}
}

func (service recipientService) ResolveForEvent(e model.EventEnvelope) ([]model.ChannelRecipients, error) {
	subs, err := service.repository.FindForEvent(e.TenantID, e.EventType)
	if err != nil {
		return nil, fmt.Errorf("failed to find subscriptions: %w", err)
	}

	if len(subs) == 0 {
		return nil, nil
	}

	filtered := service.repository.FilterSubscriptions(subs, e)
	if len(filtered) == 0 {
		return nil, nil
	}

	byChannel := groupByChannel(filtered)

	var result []model.ChannelRecipients

	for channel, userIDs := range byChannel {
		emails, err := service.repository.FindUserEmails(userIDs)
		if err != nil {
			service.logContext().Error(fmt.Errorf("failed to resolve user emails for channel %s: %v", channel, err))

			continue
		}

		if len(emails) > 0 {
			result = append(result, model.ChannelRecipients{
				Channel:    channel,
				Recipients: emails,
			})
		}
	}

	return result, nil
}

func groupByChannel(subs []model.Subscription) map[string][]bson.ObjectID {
	result := make(map[string][]bson.ObjectID)

	for _, sub := range subs {
		result[sub.Channel] = append(result[sub.Channel], sub.UserID)
	}

	return result
}

func (service recipientService) logContext() log.Logger {
	return service.logger.WithFields(map[string]interface{}{
		"service": "NOTIFIER",
		"type":    "Recipient",
	})
}
