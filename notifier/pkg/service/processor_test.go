package service

import (
	"testing"

	"notifier/pkg/config"
	"notifier/pkg/model"
)

func TestThrottleWindowForCloudLimit(t *testing.T) {
	prevWindow := config.Throttle.Window
	prevCloud := config.Throttle.CloudLimitWindow
	t.Cleanup(func() {
		config.Throttle.Window = prevWindow
		config.Throttle.CloudLimitWindow = prevCloud
	})

	config.Throttle.Window = 60
	config.Throttle.CloudLimitWindow = 7_200

	if got := throttleWindowFor("cloud_limit_threshold"); got != 7_200 {
		t.Fatalf("expected 7200 for cloud_limit_threshold, got %d", got)
	}

	if got := throttleWindowFor("topology_failed"); got != 60 {
		t.Fatalf("expected 60 for non-cloud preset, got %d", got)
	}
}

func TestPresetSuffixSplitsCloudLimitsByResource(t *testing.T) {
	cases := []struct {
		name     string
		presetID string
		event    model.EventEnvelope
		want     string
	}{
		{
			name:     "non cloud preset returns empty",
			presetID: "topology_failed",
			event:    model.EventEnvelope{Context: map[string]interface{}{"resource": "messages"}},
			want:     "",
		},
		{
			name:     "cloud limit messages",
			presetID: "cloud_limit_threshold",
			event:    model.EventEnvelope{Context: map[string]interface{}{"resource": "messages"}},
			want:     ":messages",
		},
		{
			name:     "cloud limit storage",
			presetID: "cloud_limit_threshold",
			event:    model.EventEnvelope{Context: map[string]interface{}{"resource": "storage"}},
			want:     ":storage",
		},
		{
			name:     "cloud limit without resource falls back to empty",
			presetID: "cloud_limit_threshold",
			event:    model.EventEnvelope{Context: map[string]interface{}{}},
			want:     "",
		},
		{
			name:     "cloud limit without context",
			presetID: "cloud_limit_threshold",
			event:    model.EventEnvelope{},
			want:     "",
		},
	}

	for _, tc := range cases {
		t.Run(tc.name, func(t *testing.T) {
			if got := presetSuffix(tc.presetID, tc.event); got != tc.want {
				t.Fatalf("presetSuffix(%q) = %q; want %q", tc.presetID, got, tc.want)
			}
		})
	}
}

func TestThrottleKeysIncludeResourceForCloudLimit(t *testing.T) {
	prev := config.Throttle.Mode
	t.Cleanup(func() { config.Throttle.Mode = prev })
	config.Throttle.Mode = "global_per_preset"

	event := model.EventEnvelope{
		TenantID: "tenant-1",
		Context:  map[string]interface{}{"resource": "messages"},
	}

	if got := throttleKey("cloud_limit_threshold", event); got != "throttle:tenant-1:cloud_limit_threshold:messages" {
		t.Fatalf("unexpected throttle key: %s", got)
	}

	if got := inAppThrottleKey("cloud_limit_threshold", event); got != "inapp:throttle:tenant-1:cloud_limit_threshold:no-topo:messages" {
		t.Fatalf("unexpected in-app throttle key: %s", got)
	}

	if got := bufferKey("cloud_limit_threshold", event); got != "tenant-1:cloud_limit_threshold:no-topo:messages" {
		t.Fatalf("unexpected buffer key: %s", got)
	}
}
