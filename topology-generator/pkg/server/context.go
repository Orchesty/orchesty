package server

import (
	"github.com/gin-gonic/gin"
	log "github.com/hanaboso/go-log/pkg"
	"github.com/pkg/errors"
	"net/http"
	"topology-generator/pkg/config"
	"topology-generator/pkg/services"

	"topology-generator/pkg/model"
)

// ContextWrapper ContextWrapper
type ContextWrapper struct {
	*gin.Context
	Sc *services.ServiceContainer
}

// OK OK
func (w *ContextWrapper) OK(obj ...interface{}) {
	if len(obj) > 0 {
		w.JSON(http.StatusOK, obj[0])
	} else {
		w.JSON(http.StatusNoContent, nil)
	}
}

// WithCode WithCode
func (w *ContextWrapper) WithCode(code int, obj ...interface{}) {
	w.JSON(code, obj[0])
}

// NOK NOK
func (w *ContextWrapper) NOK(err error) {
	code := http.StatusInternalServerError
	resp := gin.H{
		"code":            "INTERNAL_SERVER_ERROR",
		"codeDescription": "Internal server error occurred.",
		"details":         err.Error(),
		"debugDetails":    "",
	}

	if gin.IsDebugging() {
		b, _ := w.GetRawData()
		resp["debugDetails"] = string(b)
	}

	if mErr, ok := errors.Cause(err).(model.Error); ok {
		code = mErr.StatusCode()
		resp["code"] = mErr.Error()
		resp["codeDescription"] = mErr.Description()

		logContext().Info(err.Error()) // well-known error, so only info log
	} else {
		logContext().Error(err) // unknown error, so LOG IT!
	}

	w.JSON(code, resp)
}

// Wrap gin Context with convenient methods
func Wrap(handler func(*ContextWrapper), sc *services.ServiceContainer) func(*gin.Context) {
	return func(c *gin.Context) {
		handler(&ContextWrapper{Context: c, Sc: sc})
	}
}

// WrapBindErr Wrap error
func WrapBindErr(err model.Error, info error) error {
	return errors.Wrap(err, info.Error())
}

func logContext() log.Logger {
	return config.Logger.WithFields(map[string]interface{}{
		"service": "topology-generator",
		"type":    "server",
	})
}
