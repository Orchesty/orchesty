package storage

import (
	"context"
	"fmt"
	"slices"

	log "github.com/hanaboso/go-log/pkg"
	"github.com/hanaboso/go-mongodb"
	"go.mongodb.org/mongo-driver/v2/bson"
	mongoDriver "go.mongodb.org/mongo-driver/v2/mongo"
	"go.mongodb.org/mongo-driver/v2/mongo/options"

	"notifier/pkg/model"
)

type MongoStorage struct {
	connection    *mongodb.Connection
	subscriptions *mongoDriver.Collection
	users         *mongoDriver.Collection
	notifications *mongoDriver.Collection
	logger        log.Logger
}

func NewStorage(connection *mongodb.Connection, logger log.Logger) MongoStorage {
	service := MongoStorage{
		connection:    connection,
		subscriptions: connection.Database.Collection("subscriptions"),
		users:         connection.Database.Collection("User"),
		notifications: connection.Database.Collection("notifications"),
		logger:        logger,
	}

	service.ensureIndexes()

	return service
}

func (s MongoStorage) SaveNotification(n model.InAppNotification) error {
	ctx, cancel := s.Context()
	defer cancel()

	if _, err := s.notifications.InsertOne(ctx, n); err != nil {
		s.logContext().Error(err)

		return fmt.Errorf("failed to save notification: %w", err)
	}

	return nil
}

func (s MongoStorage) IsConnected() bool {
	return s.connection.IsConnected()
}

func (s MongoStorage) Context() (context.Context, context.CancelFunc) {
	return s.connection.Context()
}

func (s MongoStorage) ListByTenantAndUser(tenantID string, userID bson.ObjectID) ([]model.Subscription, error) {
	ctx, cancel := s.Context()
	defer cancel()

	filter := bson.D{
		{Key: model.TenantID, Value: tenantID},
		{Key: model.UserID, Value: userID},
	}

	cursor, err := s.subscriptions.Find(ctx, filter)
	if err != nil {
		s.logContext().Error(err)

		return nil, fmt.Errorf("failed to list subscriptions: %w", err)
	}
	defer cursor.Close(ctx)

	var subs []model.Subscription
	if err := cursor.All(ctx, &subs); err != nil {
		s.logContext().Error(err)

		return nil, fmt.Errorf("failed to decode subscriptions: %w", err)
	}

	return subs, nil
}

func (s MongoStorage) Upsert(sub model.Subscription) error {
	ctx, cancel := s.Context()
	defer cancel()

	filter := bson.D{
		{Key: model.TenantID, Value: sub.TenantID},
		{Key: model.UserID, Value: sub.UserID},
		{Key: model.SubjectType, Value: sub.SubjectType},
		{Key: model.SubjectID, Value: sub.SubjectID},
		{Key: model.Channel, Value: sub.Channel},
	}

	update := bson.D{{Key: "$set", Value: bson.D{
		{Key: model.Enabled, Value: sub.Enabled},
		{Key: model.Filters, Value: sub.Filters},
	}}}

	opts := options.UpdateOne().SetUpsert(true)
	if _, err := s.subscriptions.UpdateOne(ctx, filter, update, opts); err != nil {
		s.logContext().Error(err)

		return fmt.Errorf("failed to upsert subscription: %w", err)
	}

	return nil
}

func (s MongoStorage) FindForEvent(tenantID, eventType string) ([]model.Subscription, error) {
	ctx, cancel := s.Context()
	defer cancel()

	filter := bson.D{
		{Key: model.TenantID, Value: tenantID},
		{Key: model.SubjectType, Value: "event_type"},
		{Key: model.SubjectID, Value: eventType},
		{Key: model.Enabled, Value: true},
	}

	cursor, err := s.subscriptions.Find(ctx, filter)
	if err != nil {
		s.logContext().Error(err)

		return nil, fmt.Errorf("failed to find subscriptions for event: %w", err)
	}
	defer cursor.Close(ctx)

	var subs []model.Subscription
	if err := cursor.All(ctx, &subs); err != nil {
		s.logContext().Error(err)

		return nil, fmt.Errorf("failed to decode subscriptions: %w", err)
	}

	return subs, nil
}

// FindAllForEvent is the unfiltered counterpart of FindForEvent — it returns
// every Subscription for `(tenantID, event_type=eventType)` regardless of the
// `enabled` flag. The recipient resolver needs the disabled rows for
// default-subscribed presets to know which users have explicitly opted out
// (those users must NOT receive the implicit-default email).
func (s MongoStorage) FindAllForEvent(tenantID, eventType string) ([]model.Subscription, error) {
	ctx, cancel := s.Context()
	defer cancel()

	filter := bson.D{
		{Key: model.TenantID, Value: tenantID},
		{Key: model.SubjectType, Value: "event_type"},
		{Key: model.SubjectID, Value: eventType},
	}

	cursor, err := s.subscriptions.Find(ctx, filter)
	if err != nil {
		s.logContext().Error(err)

		return nil, fmt.Errorf("failed to find subscriptions for event: %w", err)
	}
	defer cursor.Close(ctx)

	var subs []model.Subscription
	if err := cursor.All(ctx, &subs); err != nil {
		s.logContext().Error(err)

		return nil, fmt.Errorf("failed to decode subscriptions: %w", err)
	}

	return subs, nil
}

// FindAllUserIDs lists every user known to this notifier instance. Used by
// the recipient resolver to seed implicit recipients for default-subscribed
// presets. Each notifier serves a single tenant (one Mongo DB per instance),
// so no tenant filter is applied here.
func (s MongoStorage) FindAllUserIDs() ([]bson.ObjectID, error) {
	ctx, cancel := s.Context()
	defer cancel()

	// Project to `_id` only; we resolve emails separately via FindUserEmails
	// when we know which users actually need to be contacted (after applying
	// the explicit opt-out list).
	cursor, err := s.users.Find(ctx, bson.D{}, options.Find().SetProjection(bson.D{{Key: "_id", Value: 1}}))
	if err != nil {
		s.logContext().Error(err)

		return nil, fmt.Errorf("failed to find users: %w", err)
	}
	defer cursor.Close(ctx)

	var users []model.User
	if err := cursor.All(ctx, &users); err != nil {
		s.logContext().Error(err)

		return nil, fmt.Errorf("failed to decode users: %w", err)
	}

	ids := make([]bson.ObjectID, len(users))
	for i, u := range users {
		ids[i] = u.ID
	}

	return ids, nil
}

func (s MongoStorage) FindUserEmails(userIDs []bson.ObjectID) ([]string, error) {
	ctx, cancel := s.Context()
	defer cancel()

	filter := bson.D{
		{Key: "_id", Value: bson.D{{Key: "$in", Value: userIDs}}},
	}

	cursor, err := s.users.Find(ctx, filter)
	if err != nil {
		s.logContext().Error(err)

		return nil, fmt.Errorf("failed to find users: %w", err)
	}
	defer cursor.Close(ctx)

	var users []model.User
	if err := cursor.All(ctx, &users); err != nil {
		s.logContext().Error(err)

		return nil, fmt.Errorf("failed to decode users: %w", err)
	}

	emails := make([]string, len(users))
	for i, u := range users {
		emails[i] = u.Email
	}

	return emails, nil
}

func (s MongoStorage) FilterSubscriptions(subs []model.Subscription, e model.EventEnvelope) []model.Subscription {
	var filtered []model.Subscription

	for _, sub := range subs {
		if sub.Filters != nil {
			if len(sub.Filters.TopologyNames) > 0 {
				if e.Topology == nil || !slices.Contains(sub.Filters.TopologyNames, e.Topology.Name) {
					continue
				}
			}
		}

		filtered = append(filtered, sub)
	}

	return filtered
}

func (s MongoStorage) ensureIndexes() {
	ctx, cancel := s.Context()
	defer cancel()

	subIndexes := []mongoDriver.IndexModel{
		{
			Keys: bson.D{
				{Key: model.TenantID, Value: 1},
				{Key: model.UserID, Value: 1},
				{Key: model.SubjectType, Value: 1},
				{Key: model.SubjectID, Value: 1},
				{Key: model.Channel, Value: 1},
			},
			Options: options.Index().SetUnique(true),
		},
		{
			Keys: bson.D{
				{Key: model.TenantID, Value: 1},
				{Key: model.SubjectID, Value: 1},
				{Key: model.Channel, Value: 1},
				{Key: model.Enabled, Value: 1},
			},
		},
	}

	if _, err := s.subscriptions.Indexes().CreateMany(ctx, subIndexes); err != nil {
		s.logContext().Error(fmt.Errorf("failed to create subscription indexes: %v", err))
	}

	userIndexes := []mongoDriver.IndexModel{
		{
			Keys:    bson.D{{Key: model.Email, Value: 1}},
			Options: options.Index().SetUnique(true),
		},
	}

	if _, err := s.users.Indexes().CreateMany(ctx, userIndexes); err != nil {
		s.logContext().Error(fmt.Errorf("failed to create user indexes: %v", err))
	}

	notifIndexes := []mongoDriver.IndexModel{
		{
			Keys:    bson.D{{Key: "createdAt", Value: 1}},
			Options: options.Index().SetExpireAfterSeconds(432000), // 5 days
		},
		{
			Keys: bson.D{
				{Key: "tenantId", Value: 1},
				{Key: "createdAt", Value: -1},
			},
		},
	}

	if _, err := s.notifications.Indexes().CreateMany(ctx, notifIndexes); err != nil {
		s.logContext().Error(fmt.Errorf("failed to create notification indexes: %v", err))
	}
}

func (s MongoStorage) logContext() log.Logger {
	return s.logger.WithFields(map[string]interface{}{
		"service": "NOTIFIER",
		"type":    "Storage",
	})
}
