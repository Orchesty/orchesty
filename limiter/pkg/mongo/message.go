package mongo

import (
	"github.com/hanaboso/go-utils/pkg/contextx"
	"github.com/hanaboso/go-utils/pkg/timex"
	"github.com/pkg/errors"
	"go.mongodb.org/mongo-driver/bson"
	"go.mongodb.org/mongo-driver/bson/primitive"
	"go.mongodb.org/mongo-driver/mongo/options"
	"limiter/pkg/enum"
	"limiter/pkg/model"
	"time"
)

type Message struct {
	Id         primitive.ObjectID `bson:"_id,omitempty"`
	Created    time.Time          `bson:"created"`
	Published  int64              `bson:"published"`
	AllowedAt  time.Time          `bson:"allowedAt"`
	LimitKey   string             `bson:"limitKey"`
	Message    *model.MessageDto  `bson:"message"`
	InProcess  bool               `bson:"inProcess"`
	Prioritize bool               `bson:"prioritize"`
}

type LimitItem struct {
	Id     string `bson:"_id"`
	Amount int    `bson:"amount"`
}

func (this MongoSvc) GetAllLimitKeys() (map[string]int, error) {
	ctx := contextx.WithTimeoutSecondsCtx(30)
	result, err := this.collection.Aggregate(ctx, bson.A{
		bson.D{{"$group", bson.D{
			{"_id", "$limitKey"},
			{"amount", bson.D{{"$sum", 1}}},
		}}},
	})
	if err != nil {
		return nil, errors.Wrap(err, "fetching limit keys")
	}

	var limits []LimitItem
	err = result.All(ctx, &limits)
	if err != nil {
		return nil, err
	}

	limitList := map[string]int{}
	for _, limit := range limits {
		limitList[limit.Id] = limit.Amount
	}

	return limitList, nil
}

func (this MongoSvc) Insert(message Message) error {
	_, err := this.collection.InsertOne(contextx.WithTimeoutSecondsCtx(30), message)
	return errors.WithMessage(err, "inserting new message")
}

func (this MongoSvc) Delete(id string) error {
	objectId, err := primitive.ObjectIDFromHex(id)
	if err != nil {
		return errors.WithMessage(err, "deleting message")
	}

	_, err = this.collection.DeleteOne(contextx.WithTimeoutSecondsCtx(30), bson.D{{
		"_id", objectId,
	}})
	if err != nil {
		return errors.Wrap(err, "deleting message")
	}

	return nil
}

func (this MongoSvc) UnmarkAllMessages() error {
	_, err := this.collection.UpdateMany(contextx.WithTimeoutSecondsCtx(30), bson.D{{}}, bson.D{{
		"$set", bson.D{{"inProcess", false}},
	}})
	if err != nil {
		return errors.Wrap(err, "unlocking all messages")
	}

	return nil
}

func (this MongoSvc) UnmarkInProcess(id string) error {
	objectId, err := primitive.ObjectIDFromHex(id)
	if err != nil {
		return errors.WithMessage(err, "unmarking process")
	}

	return this.UnmarkInProcessByObjectId(objectId)
}

func (this MongoSvc) UnmarkInProcessByObjectId(id primitive.ObjectID) error {
	_, err := this.collection.UpdateOne(contextx.WithTimeoutSecondsCtx(30), bson.D{{
		"_id", id,
	}}, bson.D{{
		"$set", bson.D{{
			"inProcess", false,
		}},
	}})
	return errors.WithMessage(err, "unmarking process by id")
}

func (this MongoSvc) FetchMessages(key string, limit int) ([]Message, error) {
	l := int64(limit)
	ctx := contextx.WithTimeoutSecondsCtx(30)
	cursor, err := this.collection.Find(
		ctx,
		bson.D{
			{"limitKey", key},
			{"allowedAt", bson.D{{"$lte", primitive.NewDateTimeFromTime(time.Now())}}},
			{"$or", bson.A{
				bson.D{{"inProcess", false}},
				// 5 Minutes lock timeout for cases when unlocking of failed messages also failed to make sure it will be processed again
				bson.D{{"allowedAt", bson.D{{"$lte", primitive.NewDateTimeFromTime(time.Now().Add(-5 * time.Minute))}}}},
			}},
		},
		&options.FindOptions{
			Limit: &l,
			Sort:  bson.D{{"prioritize", -1}, {"allowedAt", 1}},
		},
	)
	if err != nil {
		return nil, err
	}

	var messages []Message
	err = cursor.All(ctx, &messages)
	if err != nil {
		return nil, err
	}

	ids := []primitive.ObjectID{}
	for _, message := range messages {
		ids = append(ids, message.Id)
	}

	_, err = this.collection.UpdateMany(
		contextx.WithTimeoutSecondsCtx(30),
		bson.D{{
			"_id",
			bson.D{{"$in", ids}},
		}},
		bson.D{{
			"$set",
			bson.D{{"inProcess", true}},
		}},
	)
	if err != nil {
		return nil, err
	}

	return messages, nil
}

func FromDto(dto *model.MessageDto, headers map[string]interface{}, limitKey string) Message {
	value, ok := headers[enum.Header_PublishedTimestamp]
	if !ok {
		value = timex.UnixMs()
	}

	repeatDelay := dto.RepeatDelay()

	return Message{
		Created:    time.Now(),
		AllowedAt:  time.Now().Add(time.Duration(repeatDelay) * time.Second),
		LimitKey:   limitKey,
		Message:    dto,
		InProcess:  false,
		Published:  value.(int64),
		Prioritize: repeatDelay > 0,
	}
}
