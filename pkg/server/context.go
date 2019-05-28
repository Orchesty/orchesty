package server

import (
	"net/http"

	"github.com/gin-gonic/gin"
	"github.com/pkg/errors"
	log "github.com/sirupsen/logrus"

	"topology-generator/pkg/model"
)

type contextWrapper struct {
	*gin.Context
}

func (w *contextWrapper) OK(obj ...interface{}) {
	if len(obj) > 0 {
		w.JSON(http.StatusOK, obj[0])
	} else {
		w.JSON(http.StatusNoContent, nil)
	}
}

func (w *contextWrapper) WithCode(code int, obj ...interface{}) {
	w.JSON(code, obj[0])
}

func (w *contextWrapper) NOK(err error) {
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

		log.Info(err) // well-known error, so only info log
	} else {
		log.Error(err) // unknown error, so LOG IT!
	}

	w.JSON(code, resp)
}

// Wrap gin Context with convenient methods
func Wrap(handler func(*contextWrapper)) func(*gin.Context) {
	return func(c *gin.Context) {
		handler(&contextWrapper{c})
	}
}

// WrapBindErr Wrap error
func WrapBindErr(err model.Error, info error) error {
	return errors.Wrap(err, info.Error())
}
