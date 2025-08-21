package mongo

import (
	"github.com/hanaboso/go-utils/pkg/contextx"
	"github.com/pkg/errors"
	"go.mongodb.org/mongo-driver/v2/bson"
)

func (this MongoSvc) ClearAll() error {
	return errors.WithMessage(this.Clear(bson.D{}), "clearAll")
}

func (this MongoSvc) Clear(filter bson.D) error {
	ctx, _ := contextx.WithTimeoutSecondsCtx(30)
	_, err := this.collection.DeleteMany(ctx, filter)

	return errors.WithMessage(err, "Clearing documents")
}
