package mongo

import (
	"github.com/hanaboso/go-utils/pkg/contextx"
	"github.com/pkg/errors"
	"github.com/rs/zerolog/log"
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
			{"user", "orchesty"},
		},
		&options.FindOneOptions{
			Projection: bson.D{{
				"key", 1,
			}},
		},
	).Decode(&token)

	if err != nil {
		log.Fatal().Err(errors.WithMessage(err, "fetching ApiToken")).Send()
	}

	return token.Key
}
