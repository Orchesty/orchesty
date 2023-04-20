package mongo

import (
	"github.com/hanaboso/go-utils/pkg/contextx"
	"go.mongodb.org/mongo-driver/bson"
	"go.mongodb.org/mongo-driver/mongo/options"
	"limiter/pkg/config"
)

type ApiToken struct {
	Key string `bson:"key"`
}

func (this MongoSvc) GetApiToken() string {
	var token ApiToken

	err := this.connection.Database.Collection(config.MongoDb.ApiTokenCollection).FindOne(
		contextx.WithTimeoutSecondsCtx(30),
		bson.D{
			{"user", config.App.SystemUser},
		},
		&options.FindOneOptions{
			Projection: bson.D{{
				"key", 1,
			}},
		},
	).Decode(&token)

	if err != nil {
		return ""
	}

	return token.Key
}
