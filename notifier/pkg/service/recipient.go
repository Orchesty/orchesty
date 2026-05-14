package service

import (
	"fmt"

	log "github.com/hanaboso/go-log/pkg"
	"go.mongodb.org/mongo-driver/v2/bson"

	"notifier/pkg/model"
	"notifier/pkg/storage"
)

const emailChannel = "email"

type (
	RecipientService interface {
		ResolveForEvent(e model.EventEnvelope) ([]model.ChannelRecipients, error)
	}

	// recipientRepository is the narrow read surface the resolver actually
	// needs. Defining it locally (instead of taking the concrete
	// `storage.MongoStorage`) lets us inject a hand-rolled fake in unit
	// tests without spinning up Mongo. `storage.MongoStorage` satisfies it
	// implicitly via Go's structural typing.
	recipientRepository interface {
		FindForEvent(tenantID, eventType string) ([]model.Subscription, error)
		FindAllForEvent(tenantID, eventType string) ([]model.Subscription, error)
		FindAllUserIDs() ([]bson.ObjectID, error)
		FindUserEmails(userIDs []bson.ObjectID) ([]string, error)
		FilterSubscriptions(subs []model.Subscription, e model.EventEnvelope) []model.Subscription
	}

	recipientService struct {
		repository     recipientRepository
		defaultPresets map[string]struct{}
		logger         log.Logger
	}
)

func NewRecipientService(repository storage.MongoStorage, presets []model.Preset, logger log.Logger) RecipientService {
	return newRecipientService(repository, presets, logger)
}

func newRecipientService(repository recipientRepository, presets []model.Preset, logger log.Logger) recipientService {
	defaults := make(map[string]struct{})
	for _, p := range presets {
		if p.DefaultSubscribed {
			// Preset.ID matches EventEnvelope.EventType for our presets; the
			// recipient resolver looks up by event type, so we index by ID.
			defaults[p.ID] = struct{}{}
		}
	}

	return recipientService{repository, defaults, logger}
}

// ResolveForEvent computes the recipient list for an event.
//
// Two preset families are supported:
//
//  1. Explicit-only presets (DefaultSubscribed=false, the historical
//     behaviour): a user is a recipient iff there is a stored Subscription
//     with `enabled: true` matching the event's filters.
//
//  2. Default-subscribed presets (DefaultSubscribed=true): every user in the
//     local Mongo `User` collection is an implicit email recipient unless
//     they own an explicit Subscription for this `event_type` /
//     `email`-channel pair. The explicit row may be `enabled: false`
//     (opt-out) or `enabled: true` with a topology filter (limit scope).
//     The explicit row fully owns the user's choice for the email channel —
//     including filter-narrowing — so a user with a topology-filtered
//     explicit row is *not* swept back into the implicit pool when the
//     filter excludes the current event.
//
// Slack and other non-email channels stay strictly opt-in regardless of the
// preset flag — sending to a Slack workspace by default is too disruptive
// without an explicit setup step.
func (service recipientService) ResolveForEvent(e model.EventEnvelope) ([]model.ChannelRecipients, error) {
	_, isDefault := service.defaultPresets[e.EventType]

	var explicit []model.Subscription
	var err error
	if isDefault {
		// Disabled rows are still relevant — they identify users who opted
		// out of the default subscription and must not be re-included via
		// the implicit pool.
		explicit, err = service.repository.FindAllForEvent(e.TenantID, e.EventType)
	} else {
		explicit, err = service.repository.FindForEvent(e.TenantID, e.EventType)
	}
	if err != nil {
		return nil, fmt.Errorf("failed to find subscriptions: %w", err)
	}

	// Track every user that owns an explicit row per channel; these users
	// are excluded from the implicit-default pool below regardless of the
	// row's `enabled` value or its topology filter.
	explicitOwners := make(map[string]map[bson.ObjectID]struct{})
	for _, sub := range explicit {
		if explicitOwners[sub.Channel] == nil {
			explicitOwners[sub.Channel] = make(map[bson.ObjectID]struct{})
		}
		explicitOwners[sub.Channel][sub.UserID] = struct{}{}
	}

	filtered := service.repository.FilterSubscriptions(explicit, e)

	byChannel := make(map[string][]bson.ObjectID)
	for _, sub := range filtered {
		if !sub.Enabled {
			continue
		}
		byChannel[sub.Channel] = append(byChannel[sub.Channel], sub.UserID)
	}

	if isDefault {
		allUsers, allErr := service.repository.FindAllUserIDs()
		if allErr != nil {
			service.logContext().Error(fmt.Errorf("failed to list users for default-subscribed preset %s: %v", e.EventType, allErr))
		} else {
			owned := explicitOwners[emailChannel]
			for _, uid := range allUsers {
				if _, taken := owned[uid]; taken {
					continue
				}
				byChannel[emailChannel] = append(byChannel[emailChannel], uid)
			}
		}
	}

	if len(byChannel) == 0 {
		return nil, nil
	}

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

func (service recipientService) logContext() log.Logger {
	return service.logger.WithFields(map[string]interface{}{
		"service": "NOTIFIER",
		"type":    "Recipient",
	})
}
