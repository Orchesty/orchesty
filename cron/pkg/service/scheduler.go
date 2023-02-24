package service

import (
	"github.com/go-co-op/gocron"

	"cron/pkg/model"

	log "github.com/hanaboso/go-log/pkg"
)

type (
	SchedulerService interface {
		Upsert(crons []model.Cron) error
		Delete(crons []model.Cron) error
	}

	schedulerService struct {
		scheduler     *gocron.Scheduler
		startingPoint StartingPointService
		logger        log.Logger
	}
)

func NewSchedulerService(scheduler *gocron.Scheduler, startingPointService StartingPointService, logger log.Logger) SchedulerService {
	return schedulerService{scheduler, startingPointService, logger}
}

func (service schedulerService) Upsert(crons []model.Cron) error {
	if err := service.Delete(crons); err != nil {
		service.logContext().Error(err)
	}

	for _, cron := range crons {
		if _, err := service.scheduler.Cron(cron.Time).Tag(cron.Node).Do(func(cron model.Cron) func() {
			return func() {
				if err := service.startingPoint.RunTopology(cron.Topology, cron.Node, cron.Parameters); err != nil {
					service.logContext().Error(err)
				}
			}
		}(cron)); err != nil {
			service.logContext().Error(err)

			return err
		}
	}

	return nil
}

func (service schedulerService) Delete(crons []model.Cron) error {
	var tags []string

	for _, cron := range crons {
		tags = append(tags, cron.Node)
	}

	// Intentionally
	_ = service.scheduler.RemoveByTagsAny(tags...)

	return nil
}

func (service schedulerService) logContext() log.Logger {
	return service.logger.WithFields(map[string]interface{}{
		"service": "CRON",
		"type":    "Scheduler",
	})
}
