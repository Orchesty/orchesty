package service

import (
	"net/http"

	"cron/pkg/model"
	"cron/pkg/storage"
	"cron/pkg/utils"

	log "github.com/hanaboso/go-log/pkg"
	cronParser "github.com/robfig/cron/v3"
)

type (
	CronService interface {
		Select() ([]model.Cron, error)
		Upsert(crons []model.Cron) error
		Delete(crons []model.Cron) error
	}

	cronService struct {
		repository storage.MongoStorage
		scheduler  SchedulerService
		logger     log.Logger
	}
)

func NewCronService(repository storage.MongoStorage, scheduler SchedulerService, logger log.Logger) CronService {
	service := cronService{repository, scheduler, logger}

	if err := service.loadFromDatabaseToScheduler(); err != nil {
		service.logContext().Error(err)

		panic(err)
	}

	return service
}

func (service cronService) Select() ([]model.Cron, error) {
	return service.repository.FindCrons()
}

func (service cronService) Upsert(crons []model.Cron) error {
	if err := service.validate(crons); err != nil {
		service.logContext().Error(err)

		return err
	}

	if err := service.repository.UpsertCron(crons); err != nil {
		service.logContext().Error(err)

		return err
	}

	if err := service.scheduler.Upsert(crons); err != nil {
		service.logContext().Error(err)

		panic(err)
	}

	return nil
}

func (service cronService) Delete(crons []model.Cron) error {
	if err := service.repository.DeleteCron(crons); err != nil {
		service.logContext().Error(err)

		return err
	}

	if err := service.scheduler.Delete(crons); err != nil {
		service.logContext().Error(err)

		panic(err)
	}

	return nil
}

func (service cronService) loadFromDatabaseToScheduler() error {
	crons, err := service.repository.FindCrons()

	if err != nil {
		return err
	}

	return service.scheduler.Upsert(crons)
}

func (service cronService) validate(crons []model.Cron) *utils.Error {
	for _, cron := range crons {
		if _, err := cronParser.ParseStandard(cron.Time); err != nil {
			service.logContext().Error(err)

			return &utils.Error{
				Code:    http.StatusBadRequest,
				Message: "Unsupported CRON!",
			}
		}
	}

	return nil
}

func (service cronService) logContext() log.Logger {
	return service.logger.WithFields(map[string]interface{}{
		"service": "CRON",
		"type":    "Service",
	})
}
