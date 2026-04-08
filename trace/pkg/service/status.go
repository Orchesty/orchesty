package service

import (
	"trace/pkg/sender"
)

type (
	StatusService interface {
		Status() map[string]interface{}
	}

	statusService struct {
		httpSender sender.HttpSender
		statusURL  string
	}
)

func NewStatusService(httpSender sender.HttpSender, backendURL string) StatusService {
	return statusService{httpSender, sender.NewStatusURL(backendURL)}
}

func (svc statusService) Status() map[string]interface{} {
	return map[string]interface{}{
		"backend": svc.httpSender.IsConnected(svc.statusURL),
	}
}
