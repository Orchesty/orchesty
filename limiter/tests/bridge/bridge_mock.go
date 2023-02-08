package bridge

import (
	"github.com/hanaboso/go-utils/pkg/arrayx"
	"limiter/pkg/bridge"
	"limiter/pkg/enum"
	"limiter/pkg/limiter"
	"limiter/pkg/mongo"
	"strings"
)

type BridgeMock struct {
	returnError     error
	returnErrorOnce error
	Limits          *limiter.LimitSvc
	Cache           *limiter.Cache
	Mongo           mongo.MongoSvc
	ResultMessages  chan bridge.RequestMessage
}

func (this *BridgeMock) Send(message bridge.RequestMessage) error {
	if this.ResultMessages != nil {
		this.ResultMessages <- message
	}

	limitKey := message.GetHeader(enum.Header_LimitKey)
	limitKeys := arrayx.NthItemsFrom(strings.Split(limitKey, ";"), 3, 0)

	toReturn := this.returnErrorOnce
	this.returnErrorOnce = nil

	if toReturn == nil {
		toReturn = this.returnError
	}

	_ = this.Mongo.Delete(message.MessageId)
	this.Limits.FinishProcess(limitKeys)
	if toReturn != nil {
		_ = this.Mongo.UnmarkInProcess(message.MessageId)
	} else {
		this.Cache.FinishProcess(limitKey)
	}

	return toReturn
}
