package mongo

import (
	"limiter/pkg/config"

	"github.com/hanaboso/go-utils/pkg/contextx"
	"go.mongodb.org/mongo-driver/v2/bson"
	"go.mongodb.org/mongo-driver/v2/mongo/options"
)

type ApiToken struct {
	Key string `bson:"key"`
}

func (this MongoSvc) GetApiToken() string {
	var token ApiToken

	ctx, _ := contextx.WithTimeoutSecondsCtx(30)
	err := this.connection.Database.Collection(config.MongoDb.ApiTokenCollection).FindOne(
		ctx,
		bson.D{
			{"user", config.App.SystemUser},
		},
		options.FindOne().SetProjection(bson.D{{"key", 1}}),
	).Decode(&token)

	if err != nil {
		return ""
	}

	return token.Key
}
