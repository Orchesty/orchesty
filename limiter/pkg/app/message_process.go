package app

import (
	"context"
	"github.com/hanaboso/go-utils/pkg/arrayx"
	"github.com/hanaboso/go-utils/pkg/chanx"
	"github.com/hanaboso/go-utils/pkg/intx"
	"github.com/rs/zerolog/log"
	"limiter/pkg/bridge"
	"limiter/pkg/enum"
	"limiter/pkg/limiter"
	"limiter/pkg/mongo"
	"strings"
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

		log.Info().Msgf("Processing keys %v", keys)

		for key, item := range keys {
			allowed := intx.Min(this.limiterSvc.AllowedMessages(item.Keys), 30)
			if allowed <= 0 {
				continue
			}

			messages, err := this.mongoSvc.FetchMessages(key, allowed)
			log.Info().Msgf("Allowed messages for [%s]: %d, fetched messages: %d", key, allowed, len(messages))

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
				if message.Retries > 10 {
					go this.trash(message, wg)
				} else {
					// At the moment to not slow down process, messages are async which means, they can change order
					// Add setting for disabling?
					go this.send(message, wg)
				}
			}
		}
	}
}

func (this MessageProcessor) send(message mongo.Message, wg *sync.WaitGroup) {
	this.sender.Send(bridge.RequestMessage{
		MessageId: message.Id.Hex(),
		Headers:   message.Message.Headers,
		Body:      message.Message.Body,
		Published: message.Published,
	})
	wg.Done()
}

func (this MessageProcessor) trash(message mongo.Message, wg *sync.WaitGroup) {
	if err := this.mongoSvc.SendToTrash(message.Message); err != nil {
		log.Error().Err(err).Send()
	}
	_ = this.mongoSvc.Delete(message.Id.Hex())

	limitKey := message.Message.GetHeader(enum.Header_LimitKey)
	limitKeys := arrayx.NthItemsFrom(strings.Split(limitKey, ";"), 3, 0)
	this.limiterSvc.FinishProcess(limitKeys)
	this.cacheSvc.FinishProcess(limitKey)

	wg.Done()
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
	Send(message bridge.RequestMessage)
}
