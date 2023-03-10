package app

import (
	"context"
	"github.com/hanaboso/go-utils/pkg/chanx"
	"github.com/hanaboso/go-utils/pkg/intx"
	"github.com/rs/zerolog/log"
	"limiter/pkg/bridge"
	"limiter/pkg/limiter"
	"limiter/pkg/mongo"
	"sync"
	"time"
)

type MessageProcessor struct {
	sender     Sender
	cacheSvc   *limiter.Cache
	limiterSvc *limiter.LimitSvc
	mongoSvc   mongo.MongoSvc
}

func (this MessageProcessor) Start(ctx context.Context, wg *sync.WaitGroup) {
	for {
		if _, ok := chanx.TryGet(ctx.Done()); ok {
			return
		}

		time.Sleep(100 * time.Millisecond)
		keys := this.cacheSvc.NextItems()
		if len(keys) == 0 {
			continue
		}

		for key, item := range keys {
			allowed := intx.Min(this.limiterSvc.AllowedMessages(item.Keys), 30)
			if allowed <= 0 {
				continue
			}

			messages, err := this.mongoSvc.FetchMessages(key, allowed)

			if len(messages) < allowed {
				this.limiterSvc.RefreshMissingMessages(item.Keys, allowed-len(messages))
			}

			if len(messages) == 0 {
				continue
			}

			if err != nil {
				log.Error().Err(err).Send()
				break
			}

			wg.Add(len(messages))
			for _, message := range messages {
				err := this.sender.Send(bridge.RequestMessage{
					MessageId: message.Id.Hex(),
					Headers:   message.Message.Headers,
					Body:      message.Message.Body,
					Published: message.Published,
				})
				wg.Done()
				if err != nil {
					log.Error().Err(err).Send()
					if err := this.mongoSvc.UnmarkInProcessByObjectId(message.Id); err != nil {
						log.Error().Err(err).Send()
					}
				}
			}
		}
	}
}

func NewMessageProcessor(sender Sender, mongoSvc mongo.MongoSvc, limiterSvc *limiter.LimitSvc, cacheSvc *limiter.Cache) MessageProcessor {
	return MessageProcessor{
		sender:     sender,
		mongoSvc:   mongoSvc,
		limiterSvc: limiterSvc,
		cacheSvc:   cacheSvc,
	}
}

type Sender interface {
	Send(message bridge.RequestMessage) error
}
