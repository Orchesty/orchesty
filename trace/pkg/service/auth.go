package service

import (
	"encoding/json"
	"fmt"
	"net/http"
	"strings"

	log "github.com/hanaboso/go-log/pkg"

	"trace/pkg/sender"
)

type (
	AuthService interface {
		CheckLogged(authHeader string) (*LoggedUser, []byte, int, error)
	}

	LoggedUser struct {
		ID    string
		Email string
	}

	authService struct {
		sender     sender.HttpSender
		backendURL string
		logger     log.Logger
	}

	checkLoggedFlat struct {
		ID    string `json:"id"`
		Email string `json:"email"`
	}

	checkLoggedNested struct {
		User struct {
			ID    string `json:"id"`
			Email string `json:"email"`
		} `json:"user"`
	}
)

func NewAuthService(sender sender.HttpSender, backendURL string, logger log.Logger) AuthService {
	return authService{sender, strings.TrimRight(backendURL, "/"), logger}
}

func (svc authService) CheckLogged(authHeader string) (*LoggedUser, []byte, int, error) {
	url := fmt.Sprintf("%s/api/user/check_logged", svc.backendURL)

	headers := map[string]string{
		"Authorization": authHeader,
	}

	response, body, err := svc.sender.SendRaw(http.MethodGet, url, headers)
	if err != nil {
		svc.logContext().Error(err)

		return nil, nil, http.StatusBadGateway, fmt.Errorf("backend unreachable: %w", err)
	}

	if response.StatusCode != http.StatusOK {
		return nil, body, response.StatusCode, nil
	}

	user := svc.parseUser(body)

	return user, body, response.StatusCode, nil
}

// parseUser extracts the user identity from the /api/user/check_logged response.
// The endpoint returns either a flat {id, email, ...} shape or a nested {user: {id, email}, token} shape
// depending on the auth mode. We try both for forward compatibility.
func (svc authService) parseUser(body []byte) *LoggedUser {
	var flat checkLoggedFlat
	if err := json.Unmarshal(body, &flat); err == nil && flat.ID != "" {
		return &LoggedUser{ID: flat.ID, Email: flat.Email}
	}

	var nested checkLoggedNested
	if err := json.Unmarshal(body, &nested); err == nil && nested.User.ID != "" {
		return &LoggedUser{ID: nested.User.ID, Email: nested.User.Email}
	}

	return nil
}

func (svc authService) logContext() log.Logger {
	return svc.logger.WithFields(map[string]interface{}{
		"service": "TRACE",
		"type":    "Auth",
	})
}
