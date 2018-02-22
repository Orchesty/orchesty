package model

import (
	"hanaboso/topologygenerator/log"
	"net/http"
	"time"
)

func Logger(inner http.Handler, name string) http.Handler {
	return http.HandlerFunc(func(w http.ResponseWriter, r *http.Request) {
		start := time.Now()

		LogRequest(r, name, start, 200)

		inner.ServeHTTP(w, r)

	})
}

func LogRequest(r *http.Request, name string, start time.Time, statusCode int) {

	log.Infof(
		"%s[%d]\t%s\t%s\t%s",
		r.Method,
		statusCode,
		r.RequestURI,
		name,
		time.Since(start),
	)
}
