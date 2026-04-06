package handler

import (
	"encoding/json"
	"net/http"

	"go.mongodb.org/mongo-driver/v2/bson"

	"notifier/pkg/model"
	"notifier/pkg/service"
	"notifier/pkg/utils"
)

func HandleListSubscriptions(writer http.ResponseWriter, request *http.Request) {
	tenantID := request.Header.Get("X-Tenant-Id")
	userIDStr := request.Header.Get("X-User-Id")

	if tenantID == "" || userIDStr == "" {
		writeErrorResponse(writer, &utils.Error{
			Code:    http.StatusBadRequest,
			Message: "X-Tenant-Id and X-User-Id headers are required",
		})

		return
	}

	userID, err := bson.ObjectIDFromHex(userIDStr)
	if err != nil {
		writeErrorResponse(writer, &utils.Error{
			Code:    http.StatusBadRequest,
			Message: "invalid X-User-Id",
		})

		return
	}

	subs, err := service.Container.SubscriptionService.List(tenantID, userID)

	if err != nil {
		writeErrorResponse(writer, err)

		return
	}

	if subs == nil {
		subs = []model.Subscription{}
	}

	writeResponse(writer, subs)
}

type upsertRequest struct {
	EventType string            `json:"event_type"`
	Channel   string            `json:"channel"`
	Enabled   bool              `json:"enabled"`
	Filters   *model.SubFilters `json:"filters,omitempty"`
}

func HandleUpsertSubscription(writer http.ResponseWriter, request *http.Request) {
	tenantID := request.Header.Get("X-Tenant-Id")
	userIDStr := request.Header.Get("X-User-Id")

	if tenantID == "" || userIDStr == "" {
		writeErrorResponse(writer, &utils.Error{
			Code:    http.StatusBadRequest,
			Message: "X-Tenant-Id and X-User-Id headers are required",
		})

		return
	}

	userID, err := bson.ObjectIDFromHex(userIDStr)
	if err != nil {
		writeErrorResponse(writer, &utils.Error{
			Code:    http.StatusBadRequest,
			Message: "invalid X-User-Id",
		})

		return
	}

	var req upsertRequest

	if err := json.NewDecoder(request.Body).Decode(&req); err != nil {
		logContext().Error(err)
		writeErrorResponse(writer, &utils.Error{
			Code:    http.StatusBadRequest,
			Message: "invalid JSON body",
		})

		return
	}

	if req.EventType == "" {
		writeErrorResponse(writer, &utils.Error{
			Code:    http.StatusBadRequest,
			Message: "event_type is required",
		})

		return
	}

	if req.Channel == "" {
		req.Channel = "email"
	}

	sub := model.Subscription{
		TenantID:    tenantID,
		UserID:      userID,
		SubjectType: "event_type",
		SubjectID:   req.EventType,
		Channel:     req.Channel,
		Enabled:     req.Enabled,
		Filters:     req.Filters,
	}

	if err := service.Container.SubscriptionService.Upsert(sub); err != nil {
		writeErrorResponse(writer, err)

		return
	}

	subs, err := service.Container.SubscriptionService.List(tenantID, userID)
	if err != nil {
		writeErrorResponse(writer, err)

		return
	}

	if subs == nil {
		subs = []model.Subscription{}
	}

	writeResponse(writer, subs)
}
