package service

import (
	log "github.com/hanaboso/go-log/pkg"
	"go.mongodb.org/mongo-driver/v2/bson"

	"notifier/pkg/model"
	"notifier/pkg/storage"
)

type (
	SubscriptionService interface {
		List(tenantID string, userID bson.ObjectID) ([]model.Subscription, error)
		Upsert(sub model.Subscription) error
	}

	subscriptionService struct {
		repository storage.MongoStorage
		logger     log.Logger
	}
)

func NewSubscriptionService(repository storage.MongoStorage, logger log.Logger) SubscriptionService {
	return subscriptionService{repository, logger}
}

func (service subscriptionService) List(tenantID string, userID bson.ObjectID) ([]model.Subscription, error) {
	subs, err := service.repository.ListByTenantAndUser(tenantID, userID)

	if err != nil {
		service.logContext().Error(err)

		return nil, err
	}

	return subs, nil
}

func (service subscriptionService) Upsert(sub model.Subscription) error {
	if err := service.repository.Upsert(sub); err != nil {
		service.logContext().Error(err)

		return err
	}

	return nil
}

func (service subscriptionService) logContext() log.Logger {
	return service.logger.WithFields(map[string]interface{}{
		"service": "NOTIFIER",
		"type":    "Subscription",
	})
}
