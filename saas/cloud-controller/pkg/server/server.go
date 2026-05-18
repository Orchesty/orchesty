package server

import (
	"bytes"
	"context"
	"encoding/json"
	"errors"
	"fmt"
	"io"
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

type kongHealthChecker interface {
	Health() error
}

type gcsHealthChecker interface {
	Health() error
}

type instanceService interface {
	CreateInstance(request service.CreateInstanceRequest) (models.InstanceInfo, error)
	UpdateInstance(request service.UpdateInstanceRequest) (models.InstanceInfo, error)
	DeleteInstance(instance string) error
	SuspendInstance(instance string) error
	ResumeInstance(instance string) error
}

type Server struct {
	instanceService instanceService
	mongo           mongoHealthChecker
	rabbit          rabbitHealthChecker
	kubernetes      kubernetesHealthChecker
	kong            kongHealthChecker
	gcs             gcsHealthChecker
}

func New(
	instanceService instanceService,
	mongo mongoHealthChecker,
	rabbit rabbitHealthChecker,
	kubernetes kubernetesHealthChecker,
	kong kongHealthChecker,
	gcs gcsHealthChecker,
) http.Handler {
	server := &Server{
		instanceService: instanceService,
		mongo:           mongo,
		rabbit:          rabbit,
		kubernetes:      kubernetes,
		kong:            kong,
		gcs:             gcs,
	}

	mux := http.NewServeMux()
	mux.HandleFunc("GET /status", server.statusHandler)
	mux.HandleFunc("POST /instance", server.createInstance)
	mux.HandleFunc("PATCH /instance", server.updateInstance)
	mux.HandleFunc("DELETE /instance", server.deleteInstance)
	mux.HandleFunc("POST /instance/suspend", server.suspendInstance)
	mux.HandleFunc("POST /instance/resume", server.resumeInstance)

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

	// Kong health check
	if config.Kong.Enabled {
		wg.Add(1)
		go func() {
			defer wg.Done()
			ctx, cancel := context.WithTimeout(context.Background(), checkTimeout)
			defer cancel()

			done := make(chan error, 1)
			go func() {
				done <- s.kong.Health()
			}()

			select {
			case err := <-done:
				mu.Lock()
				if err != nil {
					checks["kong"] = "error"
					errs = append(errs, fmt.Sprintf("kong: %s", err.Error()))
				} else {
					checks["kong"] = "ok"
				}
				mu.Unlock()
			case <-ctx.Done():
				mu.Lock()
				checks["kong"] = "timeout"
				errs = append(errs, "kong: health check timed out")
				mu.Unlock()
			}
		}()
	}

	// GCS health check
	if config.GCS.Enabled {
		wg.Add(1)
		go func() {
			defer wg.Done()
			ctx, cancel := context.WithTimeout(context.Background(), checkTimeout)
			defer cancel()

			done := make(chan error, 1)
			go func() {
				done <- s.gcs.Health()
			}()

			select {
			case err := <-done:
				mu.Lock()
				if err != nil {
					checks["gcs"] = "error"
					errs = append(errs, fmt.Sprintf("gcs: %s", err.Error()))
				} else {
					checks["gcs"] = "ok"
				}
				mu.Unlock()
			case <-ctx.Done():
				mu.Lock()
				checks["gcs"] = "timeout"
				errs = append(errs, "gcs: health check timed out")
				mu.Unlock()
			}
		}()
	}

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
	rawBody, err := readRawBody(request)
	if err != nil {
		writeErrorWithContext(writer, request, http.StatusBadRequest, err, map[string]string{"rawBody": rawBody})
		return
	}

	if err := decodeJSONBody(rawBody, &body); err != nil {
		writeErrorWithContext(writer, request, http.StatusBadRequest, err, map[string]string{"rawBody": rawBody})
		return
	}

	result, err := s.instanceService.CreateInstance(body)
	if err != nil {
		var inputErr *service.InputError
		switch {
		case errors.As(err, &inputErr):
			writeErrorWithContext(writer, request, http.StatusBadRequest, err, body)
		case errors.Is(err, service.ErrInstanceUnavailable):
			writeErrorWithContext(writer, request, http.StatusConflict, err, body)
		default:
			writeErrorWithContext(writer, request, http.StatusInternalServerError, err, body)
		}
		return
	}

	writeJSON(writer, http.StatusCreated, result)
}

func (s *Server) deleteInstance(writer http.ResponseWriter, request *http.Request) {
	input := map[string]string{
		"instance": request.URL.Query().Get("instance"),
		"query":    request.URL.RawQuery,
	}

	if err := s.instanceService.DeleteInstance(input["instance"]); err != nil {
		var inputErr *service.InputError
		if errors.As(err, &inputErr) {
			writeErrorWithContext(writer, request, http.StatusBadRequest, err, input)
			return
		}

		writeErrorWithContext(writer, request, http.StatusInternalServerError, err, input)
		return
	}

	writer.WriteHeader(http.StatusNoContent)
}

func (s *Server) updateInstance(writer http.ResponseWriter, request *http.Request) {
	var body service.UpdateInstanceRequest
	rawBody, err := readRawBody(request)
	if err != nil {
		writeErrorWithContext(writer, request, http.StatusBadRequest, err, map[string]string{"rawBody": rawBody})
		return
	}

	if err := decodeJSONBody(rawBody, &body); err != nil {
		writeErrorWithContext(writer, request, http.StatusBadRequest, err, map[string]string{"rawBody": rawBody})
		return
	}

	result, err := s.instanceService.UpdateInstance(body)
	if err != nil {
		var inputErr *service.InputError
		switch {
		case errors.As(err, &inputErr):
			writeErrorWithContext(writer, request, http.StatusBadRequest, err, body)
		default:
			writeErrorWithContext(writer, request, http.StatusInternalServerError, err, body)
		}
		return
	}

	writeJSON(writer, http.StatusOK, result)
}

func (s *Server) suspendInstance(writer http.ResponseWriter, request *http.Request) {
	var body models.SuspendInstanceRequest
	rawBody, err := readRawBody(request)
	if err != nil {
		writeErrorWithContext(writer, request, http.StatusBadRequest, err, map[string]string{"rawBody": rawBody})
		return
	}

	if err := decodeJSONBody(rawBody, &body); err != nil {
		writeErrorWithContext(writer, request, http.StatusBadRequest, err, map[string]string{"rawBody": rawBody})
		return
	}

	if err := s.instanceService.SuspendInstance(body.Instance); err != nil {
		var inputErr *service.InputError
		if errors.As(err, &inputErr) {
			writeErrorWithContext(writer, request, http.StatusBadRequest, err, body)
			return
		}

		writeErrorWithContext(writer, request, http.StatusInternalServerError, err, body)
		return
	}

	response := models.SuspendInstanceResponse{
		Instance: body.Instance,
		State:    "suspended",
	}

	writeJSON(writer, http.StatusOK, response)
}

func (s *Server) resumeInstance(writer http.ResponseWriter, request *http.Request) {
	var body models.ResumeInstanceRequest
	rawBody, err := readRawBody(request)
	if err != nil {
		writeErrorWithContext(writer, request, http.StatusBadRequest, err, map[string]string{"rawBody": rawBody})
		return
	}

	if err := decodeJSONBody(rawBody, &body); err != nil {
		writeErrorWithContext(writer, request, http.StatusBadRequest, err, map[string]string{"rawBody": rawBody})
		return
	}

	if err := s.instanceService.ResumeInstance(body.Instance); err != nil {
		var inputErr *service.InputError
		if errors.As(err, &inputErr) {
			writeErrorWithContext(writer, request, http.StatusBadRequest, err, body)
			return
		}

		writeErrorWithContext(writer, request, http.StatusInternalServerError, err, body)
		return
	}

	response := models.ResumeInstanceResponse{
		Instance: body.Instance,
		State:    "active",
	}

	writeJSON(writer, http.StatusOK, response)
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

func writeErrorWithContext(writer http.ResponseWriter, request *http.Request, statusCode int, err error, input any) {
	logErrorWithContext(request, input, err)
	writeError(writer, statusCode, err)
}

func logErrorWithContext(request *http.Request, input any, err error) {
	inputJSON, marshalErr := json.Marshal(input)
	if marshalErr != nil {
		inputJSON = []byte(fmt.Sprintf("%v", input))
	}

	config.Logger.Error(fmt.Errorf(
		"request failed: route=%s %s, input=%s, error=%w",
		request.Method,
		request.URL.Path,
		string(inputJSON),
		err,
	))
}

func readRawBody(request *http.Request) (string, error) {
	bodyBytes, err := io.ReadAll(request.Body)
	if err != nil {
		return "", fmt.Errorf("failed to read request body: %w", err)
	}

	return string(bodyBytes), nil
}

func decodeJSONBody(rawBody string, destination any) error {
	decoder := json.NewDecoder(bytes.NewReader([]byte(rawBody)))
	decoder.DisallowUnknownFields()

	if err := decoder.Decode(destination); err != nil {
		return err
	}

	if err := decoder.Decode(&struct{}{}); err != io.EOF {
		return errors.New("request body must contain only one JSON object")
	}

	return nil
}
