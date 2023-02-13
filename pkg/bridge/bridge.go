package bridge

import (
	"bytes"
	"encoding/json"
	"github.com/hanaboso/go-utils/pkg/arrayx"
	"github.com/hanaboso/go-utils/pkg/timex"
	"github.com/pkg/errors"
	"github.com/rs/zerolog/log"
	"io"
	"limiter/pkg/enum"
	"limiter/pkg/limiter"
	"limiter/pkg/mongo"
	"net/http"
	"strings"
	"time"
)

type BridgeSvc struct {
	mongo  mongo.MongoSvc
	limits *limiter.LimitSvc
	cache  *limiter.Cache
}

var (
	lastKey  string
	keyUntil int64
)

func (this BridgeSvc) Send(message RequestMessage) error {
	data, _ := json.Marshal(message)
	request, _ := http.NewRequest("POST", message.BridgeUrl(), bytes.NewReader(data))

	now := timex.UnixMs()
	if lastKey == "" || keyUntil < now {
		lastKey = this.mongo.GetApiToken()
		keyUntil = now + int64(time.Second)
	}

	if lastKey != "" {
		request.Header.Add("orchesty-api-key", lastKey)
	}
	request.Header.Add("Content-Type", "application/json")

	limitKey := message.GetHeader(enum.Header_LimitKey)
	limitKeys := arrayx.NthItemsFrom(strings.Split(limitKey, ";"), 3, 0)
	var responseData []byte
	var responseMessage ResultMessage

	response, err := http.DefaultClient.Do(request)
	if err != nil {
		log.Error().Err(errors.Wrap(err, "sending message to bridge")).Send()
		goto FINISH_PROCESS
	}

	responseData, _ = io.ReadAll(response.Body)
	err = json.Unmarshal(responseData, &responseMessage)
	if err != nil {
		log.Error().Err(errors.Wrap(err, "parsing bridge response")).Send()
		goto FINISH_PROCESS
	}

	if !responseMessage.Ok {
		goto FINISH_PROCESS
	}

	err = this.mongo.Delete(responseMessage.MessageId)
	if err != nil {
		log.Error().Err(errors.Wrap(err, "removing successfully processed message")).Send()
		goto FINISH_PROCESS
	}

FINISH_PROCESS:
	this.limits.FinishProcess(limitKeys)
	if err != nil || !responseMessage.Ok {
		if errMark := this.mongo.UnmarkInProcess(message.MessageId); errMark != nil {
			log.Error().Err(errors.Wrap(errMark, "unmarking processed message")).Send()
		}
	} else {
		this.cache.FinishProcess(limitKey)
	}

	return nil
}

func NewBridgeSvc(mongo mongo.MongoSvc, limits *limiter.LimitSvc, cache *limiter.Cache) BridgeSvc {
	return BridgeSvc{
		mongo:  mongo,
		limits: limits,
		cache:  cache,
	}
}
