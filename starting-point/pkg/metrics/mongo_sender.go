package metrics

import (
	"go.mongodb.org/mongo-driver/bson"
	"starting-point/pkg/config"
	"starting-point/pkg/storage"
)

type mongo struct{}

func (mdb mongo) SendMetrics(tags map[string]interface{}, fields map[string]interface{}) {
	bTags := make(bson.M)
	for k, v := range tags {
		bTags[k] = v
	}
	bFields := make(bson.M)
	for k, v := range fields {
		bFields[k] = v
	}

	if err := storage.Mongo.SaveMetrics(bson.M{
		"tags":   bTags,
		"fields": bFields,
	}); err != nil {
		config.Config.Logger.Error(err)
	}
}

func newMongoSender() Sender {
	return mongo{}
}
