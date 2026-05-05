package service

import (
	"errors"
	"testing"
)

func TestParseQuotaError_FullPayload(t *testing.T) {
	body := []byte(`{"code":"QUOTA_EXCEEDED","limit":100,"used":100,"resetAt":"2026-05-04T00:00:00+00:00"}`)

	err := parseQuotaError(body)

	if !errors.Is(err, ErrQuotaExceeded) {
		t.Fatalf("expected error to satisfy ErrQuotaExceeded, got %v", err)
	}

	var qe *QuotaError
	if !errors.As(err, &qe) {
		t.Fatalf("expected error to unwrap to *QuotaError")
	}
	if qe.Limit != 100 {
		t.Fatalf("expected limit=100, got %d", qe.Limit)
	}
	if qe.Used != 100 {
		t.Fatalf("expected used=100, got %d", qe.Used)
	}
	if qe.ResetAt != "2026-05-04T00:00:00+00:00" {
		t.Fatalf("expected resetAt to round-trip, got %q", qe.ResetAt)
	}
}

func TestParseQuotaError_MalformedBodyStillSatisfiesSentinel(t *testing.T) {
	// The body is intentionally garbage. The trace bridge MUST NOT fall
	// through to the generic 502 path on a 429 — UX would suddenly become
	// "Bad Gateway" instead of "Daily limit reached".
	err := parseQuotaError([]byte("not json"))

	if !errors.Is(err, ErrQuotaExceeded) {
		t.Fatalf("malformed quota body should still satisfy ErrQuotaExceeded, got %v", err)
	}

	var qe *QuotaError
	if !errors.As(err, &qe) {
		t.Fatalf("expected *QuotaError, got %T", err)
	}
	if qe.Limit != 0 || qe.Used != 0 || qe.ResetAt != "" {
		t.Fatalf("expected zero counters on malformed body, got %+v", qe)
	}
}

func TestUpstreamErrorCode_QuotaErrorStillMapsTo502(t *testing.T) {
	// Quota errors short-circuit BEFORE upstreamErrorCode is consulted (via
	// dispatchQuotaIfApplicable). This test guards the fallback in case the
	// handler order ever regresses: a quota error reaching upstreamErrorCode
	// must NOT silently masquerade as 401 (or anything other than 502), so
	// the generic-error frame remains a faithful description of the
	// underlying transport.
	err := &QuotaError{Limit: 50, Used: 50}

	if got := upstreamErrorCode(err); got != 502 {
		t.Fatalf("expected 502 for quota error in fallback path, got %d", got)
	}
}
