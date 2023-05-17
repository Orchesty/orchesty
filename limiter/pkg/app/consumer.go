package app

import (
	"github.com/hanaboso/go-rabbitmq/pkg/rabbitmq"
	"github.com/hanaboso/go-utils/pkg/arrayx"
	"github.com/pkg/errors"
	"github.com/rs/zerolog/log"
	"limiter/pkg/enum"
	"limiter/pkg/limiter"
	"limiter/pkg/model"
	"limiter/pkg/mongo"
	"strings"
)

func ProcessMessage(mongoSvc mongo.MongoSvc, cacheSvc *limiter.Cache, limiterSvc *limiter.LimitSvc) rabbitmq.JsonConsumerCallback[model.MessageDto] {
	return func(dto *model.MessageDto, headers map[string]interface{}) rabbitmq.Acked {
		limitKey, limits, err := dto.ParseLimitKeys()
		if err != nil {
			log.Error().Err(err).Send()
			return rabbitmq.Reject
		}

		cacheSvc.RegisterKey(limitKey)
		limiterSvc.UpsertLimits(limits)

		// Lower available limits for returned keys if any to try to slow down
		resultCode := dto.GetIntHeader(enum.Header_ResultCode)
		if resultCode == enum.ResultCode_LimitExceeded {
			limitKeys := arrayx.NthItemsFrom(strings.Split(limitKey, ";"), 3, 0)
			limiterSvc.FinishProcess(limitKeys)
		}

		err = mongoSvc.Insert(mongo.FromDto(dto, headers, limitKey))
		if err != nil {
			log.Error().Err(errors.WithMessage(err, "removing unexpected message")).Send()
			return rabbitmq.Reject
		}

		return rabbitmq.Ack
	}
}
