package services

import (
	"rabbitmq-telegraf/pkg/config"

	log "github.com/sirupsen/logrus"
)

type SenderSvc interface {
	Send(metrics []Queue) error
}

type Sender struct {
	sender    SenderSvc
	workQueue <-chan []Queue
}

func (s *Sender) Start() {
	for w := range s.workQueue {
		if err := s.sender.Send(w); err != nil {
			log.Error(err)
		}
	}
}

func NewSenderSvc(workQueue <-chan []Queue) Sender {
	var s SenderSvc
	if config.App.Output == config.OUTPUT_MONGO {
		s = NewMongoDbSenderSvc()
	} else {
		s = NewInfluxDbSenderSvc()
	}

	return Sender{
		sender:    s,
		workQueue: workQueue,
	}
}
