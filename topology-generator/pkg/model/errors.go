package model

import (
	"net/http"
	"strconv"

	"github.com/pkg/errors"
)

const (
	// ErrNotFound ErrNotFound
	ErrNotFound Error = iota
	// ErrRequestMalformed ErrRequestMalformed
	ErrRequestMalformed
	// ErrBadCredentials ErrBadCredentials
	ErrBadCredentials
	// ErrUnauthorized ErrUnauthorized
	ErrUnauthorized
	// ErrAlreadyRegistered ErrAlreadyRegistered
	ErrAlreadyRegistered
)

var knownErrors = map[Error]string{
	ErrBadCredentials:    "BAD_CREDENTIALS",
	ErrNotFound:          "NOT_FOUND",
	ErrRequestMalformed:  "REQUEST_MALFORMED",
	ErrUnauthorized:      "UNAUTHORIZED",
	ErrAlreadyRegistered: "ALREADY_REGISTERED",
}

var knownErrorDescriptions = map[Error]string{
	ErrBadCredentials:    "Bad credentials.",
	ErrNotFound:          "NOT_FOUND",
	ErrRequestMalformed:  "Request body can't be decoded.",
	ErrUnauthorized:      "UNAUTHORIZED",
	ErrAlreadyRegistered: "ALREADY_REGISTERED",
}

var knownStatusCodes = map[Error]int{
	ErrBadCredentials: http.StatusUnauthorized,
	ErrUnauthorized:   http.StatusUnauthorized,
	ErrNotFound:       http.StatusNotFound,
}

// Error Error
type Error int

// StatusCode StatusCode
func (err Error) StatusCode() int {
	code, ok := knownStatusCodes[err]
	if !ok {
		return http.StatusBadRequest
	}
	return code
}

// Error returns code of error
func (err Error) Error() string {
	s, ok := knownErrors[err]
	if !ok {
		panic("unknown value " + strconv.Itoa(int(err)) + " of Error") // panics if not found
	}
	return s
}

// Description returns description of error
func (err Error) Description() string {
	s, ok := knownErrorDescriptions[err]
	if !ok {
		panic("unknown value " + strconv.Itoa(int(err)) + " of Error") // panics if not found
	}
	return s
}

// IsCauseOf IsCauseOf
func (err Error) IsCauseOf(wrappedErr error) bool {
	return errors.Cause(wrappedErr) == err
}

// IsNotCauseOf IsNotCauseOf
func (err Error) IsNotCauseOf(wrappedErr error) bool {
	return !err.IsCauseOf(wrappedErr)
}
