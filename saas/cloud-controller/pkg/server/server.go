package server

import (
	"context"
	"encoding/json"
	"errors"
	"fmt"
	"net/http"
	"strings"
	"sync"
	"time"

	"cloud-controller/pkg/config"
	"cloud-controller/pkg/models"
	"cloud-controller/pkg/service"
)

type mongoHealthChecker interface {
	Ping() error
}

type rabbitHealthChecker interface {
	Health() error
}

type kubernetesHealthChecker interface {
	Health() error
}

type instanceService interface {
	CreateInstance(request service.CreateInstanceRequest) (models.InstanceInfo, error)
	UpdateInstance(request service.UpdateInstanceRequest) (models.InstanceInfo, error)
	DeleteInstance(instance string) error
}

type Server struct {
	instanceService instanceService
	mongo           mongoHealthChecker
	rabbit          rabbitHealthChecker
	kubernetes      kubernetesHealthChecker
}

func New(
	instanceService instanceService,
	mongo mongoHealthChecker,
	rabbit rabbitHealthChecker,
	kubernetes kubernetesHealthChecker,
) http.Handler {
	server := &Server{
		instanceService: instanceService,
		mongo:           mongo,
		rabbit:          rabbit,
		kubernetes:      kubernetes,
	}

	mux := http.NewServeMux()
	mux.HandleFunc("GET /status", server.statusHandler)
	mux.HandleFunc("POST /instance", server.createInstance)
	mux.HandleFunc("PATCH /instance", server.updateInstance)
	mux.HandleFunc("DELETE /instance", server.deleteInstance)

	return mux
}

func (s *Server) statusHandler(writer http.ResponseWriter, _ *http.Request) {
	checks := make(map[string]string)
	var errs []string
	var mu sync.Mutex
	var wg sync.WaitGroup
	checkTimeout := 5 * time.Second

	// RabbitMQ health check
	wg.Add(1)
	go func() {
		defer wg.Done()
		ctx, cancel := context.WithTimeout(context.Background(), checkTimeout)
		defer cancel()

		done := make(chan error, 1)
		go func() {
			done <- s.rabbit.Health()
		}()

		select {
		case err := <-done:
			mu.Lock()
			if err != nil {
				checks["rabbitmq"] = "error"
				errs = append(errs, fmt.Sprintf("rabbitmq: %s", err.Error()))
			} else {
				checks["rabbitmq"] = "ok"
			}
			mu.Unlock()
		case <-ctx.Done():
			mu.Lock()
			checks["rabbitmq"] = "timeout"
			errs = append(errs, "rabbitmq: health check timed out")
			mu.Unlock()
		}
	}()

	// MongoDB health check
	wg.Add(1)
	go func() {
		defer wg.Done()
		ctx, cancel := context.WithTimeout(context.Background(), checkTimeout)
		defer cancel()

		done := make(chan error, 1)
		go func() {
			done <- s.mongo.Ping()
		}()

		select {
		case err := <-done:
			mu.Lock()
			if err != nil {
				checks["mongodb"] = "error"
				errs = append(errs, fmt.Sprintf("mongodb: %s", err.Error()))
			} else {
				checks["mongodb"] = "ok"
			}
			mu.Unlock()
		case <-ctx.Done():
			mu.Lock()
			checks["mongodb"] = "timeout"
			errs = append(errs, "mongodb: health check timed out")
			mu.Unlock()
		}
	}()

	// Kubernetes health check
	wg.Add(1)
	go func() {
		defer wg.Done()
		ctx, cancel := context.WithTimeout(context.Background(), checkTimeout)
		defer cancel()

		done := make(chan error, 1)
		go func() {
			done <- s.kubernetes.Health()
		}()

		select {
		case err := <-done:
			mu.Lock()
			if err != nil {
				checks["k8s"] = "error"
				errs = append(errs, fmt.Sprintf("k8s: %s", err.Error()))
			} else {
				checks["k8s"] = "ok"
			}
			mu.Unlock()
		case <-ctx.Done():
			mu.Lock()
			checks["k8s"] = "timeout"
			errs = append(errs, "k8s: health check timed out")
			mu.Unlock()
		}
	}()

	wg.Wait()

	if len(errs) > 0 {
		writeJSON(writer, http.StatusServiceUnavailable, map[string]any{
			"status": "error",
			"checks": checks,
			"error":  strings.Join(errs, "; "),
		})
		return
	}

	writeJSON(writer, http.StatusOK, map[string]any{
		"status": "ok",
		"checks": checks,
	})
}

func (s *Server) createInstance(writer http.ResponseWriter, request *http.Request) {
	var body service.CreateInstanceRequest
	decoder := json.NewDecoder(request.Body)
	decoder.DisallowUnknownFields()

	if err := decoder.Decode(&body); err != nil {
		writeError(writer, http.StatusBadRequest, err)
		return
	}

	result, err := s.instanceService.CreateInstance(body)
	if err != nil {
		switch {
		case errors.Is(err, service.ErrInstanceDisplayNameRequired):
			writeError(writer, http.StatusBadRequest, err)
		case errors.Is(err, service.ErrInstanceUnavailable):
			writeError(writer, http.StatusConflict, err)
		default:
			writeError(writer, http.StatusInternalServerError, err)
		}
		return
	}

	writeJSON(writer, http.StatusCreated, result)
}

func (s *Server) deleteInstance(writer http.ResponseWriter, request *http.Request) {
	if err := s.instanceService.DeleteInstance(request.URL.Query().Get("instance")); err != nil {
		if errors.Is(err, service.ErrInstanceRequired) {
			writeError(writer, http.StatusBadRequest, err)
			return
		}

		writeError(writer, http.StatusInternalServerError, err)
		return
	}

	writer.WriteHeader(http.StatusNoContent)
}

func (s *Server) updateInstance(writer http.ResponseWriter, request *http.Request) {
	var body service.UpdateInstanceRequest
	decoder := json.NewDecoder(request.Body)
	decoder.DisallowUnknownFields()

	if err := decoder.Decode(&body); err != nil {
		writeError(writer, http.StatusBadRequest, err)
		return
	}

	result, err := s.instanceService.UpdateInstance(body)
	if err != nil {
		switch {
		case errors.Is(err, service.ErrInstanceRequired), errors.Is(err, service.ErrInstanceDisplayNameRequired):
			writeError(writer, http.StatusBadRequest, err)
		default:
			writeError(writer, http.StatusInternalServerError, err)
		}
		return
	}

	writeJSON(writer, http.StatusOK, result)
}

func writeJSON(writer http.ResponseWriter, statusCode int, body any) {
	writer.Header().Set("Content-Type", "application/json")
	writer.WriteHeader(statusCode)
	if err := json.NewEncoder(writer).Encode(body); err != nil {
		config.Logger.Error(fmt.Errorf("failed to encode JSON response: %w", err))
	}
}

func writeError(writer http.ResponseWriter, statusCode int, err error) {
	writeJSON(writer, statusCode, map[string]string{"error": err.Error()})
}
