package service

import (
	"net/http"
	"time"

	"github.com/go-co-op/gocron"
	"github.com/hanaboso/go-mongodb"

	"cron/pkg/config"
	"cron/pkg/sender"
	"cron/pkg/storage"
)

var Container container

type container struct {
	StatusService StatusService
	CronService   CronService
}

func Load() error {
	connection := &mongodb.Connection{}
	connection.Connect(config.Mongo.Dsn)

	scheduler := gocron.NewScheduler(time.UTC)
	scheduler.TagsUnique()
	scheduler.StartAsync()

	startingPoint := NewStartingPointService(
		sender.NewHttpSender(&http.Client{
			Timeout: time.Duration(config.StartingPoint.Timeout) * time.Second,
		}, config.Logger, config.StartingPoint.Dsn),
		config.Logger,
		config.StartingPoint.ApiKey,
	)

	Container = container{
		StatusService: NewStatusService(connection, scheduler, startingPoint),
		CronService: NewCronService(
			storage.NewCronStorage(connection, config.Logger, config.Mongo.Collection),
			NewSchedulerService(scheduler, startingPoint, config.Logger),
			config.Logger,
		),
	}

	return nil
}
