package mongo

import (
	"github.com/hanaboso/go-utils/pkg/contextx"
	"github.com/pkg/errors"
	"go.mongodb.org/mongo-driver/bson"
)

func (this MongoSvc) ClearAll() error {
	return errors.WithMessage(this.Clear(bson.D{}), "clearAll")
}

func (this MongoSvc) Clear(filter bson.D) error {
	_, err := this.collection.DeleteMany(contextx.WithTimeoutSecondsCtx(30), filter)

	return errors.WithMessage(err, "Clearing documents")
}
