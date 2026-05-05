package service

import "errors"

// ErrUnauthorized is wrapped by manifest/AI service errors when the upstream
// HTTP call returned 401/403. The trace WebSocket dispatcher uses
// errors.Is(err, ErrUnauthorized) to relay the auth failure to the client as
// a code:401 frame so the FE can rotate the access token silently instead of
// surfacing a confusing 502 ("Bad Gateway") in the chat.
var ErrUnauthorized = errors.New("upstream unauthorized")

// ErrQuotaExceeded is wrapped by AI service errors when platform-services
// returns 429 with `code: "QUOTA_EXCEEDED"` (instance counter cap or cloud
// relay defensive limit). The dispatcher detects it via errors.Is and emits
// a `quota_exceeded` WS frame instead of the generic `error` frame, so the
// UI can render an info card with the remaining-time CTA. The structured
// payload is carried by `QuotaError`, which both wraps the sentinel and
// exposes the counters.
var ErrQuotaExceeded = errors.New("trace quota exceeded")

// QuotaError is the error returned by AIService.SendChat when
// platform-services responds 429 with the documented JSON body. It carries
// the limit/used/resetAt counters needed by the FE info card. Always
// errors.Is-equal to ErrQuotaExceeded, so callers can branch with a single
// errors.Is check before extracting the payload via errors.As.
type QuotaError struct {
	Limit   int
	Used    int
	ResetAt string
}

// Error implements error.
func (e *QuotaError) Error() string {
	return "trace quota exceeded"
}

// Is allows errors.Is(err, ErrQuotaExceeded) without forcing callers to know
// the concrete QuotaError type.
func (e *QuotaError) Is(target error) bool {
	return target == ErrQuotaExceeded
}
