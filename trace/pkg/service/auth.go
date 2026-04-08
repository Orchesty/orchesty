package service

import (
	"fmt"
	"net/http"
	"strings"

	log "github.com/hanaboso/go-log/pkg"

	"trace/pkg/sender"
)

type (
	AuthService interface {
		CheckLogged(authHeader string) ([]byte, int, error)
	}

	authService struct {
		sender     sender.HttpSender
		backendURL string
		logger     log.Logger
	}
)

func NewAuthService(sender sender.HttpSender, backendURL string, logger log.Logger) AuthService {
	return authService{sender, strings.TrimRight(backendURL, "/"), logger}
}

func (svc authService) CheckLogged(authHeader string) ([]byte, int, error) {
	url := fmt.Sprintf("%s/api/user/check_logged", svc.backendURL)

	headers := map[string]string{
		"Authorization": authHeader,
	}

	response, body, err := svc.sender.SendRaw(http.MethodGet, url, headers)
	if err != nil {
		svc.logContext().Error(err)

		return nil, http.StatusBadGateway, fmt.Errorf("backend unreachable: %w", err)
	}

	return body, response.StatusCode, nil
}

func (svc authService) logContext() log.Logger {
	return svc.logger.WithFields(map[string]interface{}{
		"service": "TRACE",
		"type":    "Auth",
	})
}
